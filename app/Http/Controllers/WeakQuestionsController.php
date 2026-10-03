<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\QuestionBank;
use App\Models\UserWeakQuestion;
use App\Services\SubscriptionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WeakQuestionsController extends Controller
{
    public function __construct(
        protected SubscriptionManager $subscriptionManager
    ) {}

    /**
     * Render the dedicated "Revision & Weak MCQs" candidate portal page,
     * or return JSON summary if requested.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $user = $request->user();

        $weakQuery = UserWeakQuestion::query()
            ->where('user_id', $user->id)
            ->with(['question', 'domain']);

        $domainId = $request->integer('domain_id');
        if ($domainId > 0) {
            $weakQuery->where('domain_id', $domainId);
        }

        $allRecords = $weakQuery->latest('last_practiced_at')->get();

        $activeItems = $allRecords->where('status', 'active')->values();
        $masteredItems = $allRecords->where('status', 'mastered')->values();

        // Group active items by subject or domain
        $bySubject = [];
        foreach ($activeItems as $item) {
            $domainName = $item->domain ? $item->domain->name : 'General';
            $bySubject[$domainName] = ($bySubject[$domainName] ?? 0) + 1;
        }

        $summary = [
            'total_active' => $activeItems->count(),
            'total_mastered' => $masteredItems->count(),
            'total_tracked' => $allRecords->count(),
            'by_subject' => $bySubject,
        ];

        $items = $activeItems->map(fn (UserWeakQuestion $w): array => [
            'id' => $w->id,
            'question_id' => $w->question_id,
            'domain_id' => $w->domain_id,
            'domain_name' => $w->domain ? $w->domain->name : 'General',
            'question_text' => $w->question->question_text,
            'code' => $w->question->code,
            'consecutive_correct' => $w->consecutive_correct,
            'revealed_count' => $w->revealed_count,
            'failed_count' => $w->failed_count,
            'last_practiced_at' => $w->last_practiced_at?->diffForHumans(),
            'status' => $w->status,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'summary' => $summary,
                'items' => $items,
            ]);
        }

        return Inertia::render('WeakQuestions/Index', [
            'summary' => $summary,
            'items' => $items,
            'selectedDomainId' => $domainId > 0 ? $domainId : null,
        ]);
    }

    /**
     * Reveal the correct answer and explanation for a specific question on demand.
     * Forfeits marks by logging this question into the candidate's Weak MCQs.
     */
    public function reveal(Request $request, QuestionBank $question): JsonResponse
    {
        $user = $request->user();

        // Check domain subscription if linked to a domain
        $domainId = $request->integer('domain_id');
        $domain = $domainId > 0
            ? Domain::find($domainId)
            : $question->modules()->first()?->domain;

        if ($domain instanceof Domain && ! $this->subscriptionManager->hasActiveAccess($user, $domain)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Active subscription required.',
            ], 403);
        }

        // Register or increment in user_weak_questions
        /** @var UserWeakQuestion $weak */
        $weak = UserWeakQuestion::firstOrNew([
            'user_id' => $user->id,
            'question_id' => $question->id,
        ]);

        if ($domain instanceof Domain && ! $weak->domain_id) {
            $weak->domain_id = max(0, (int) $domain->id);
        }

        $weak->recordReveal();

        $payload = $question->payload ?? [];
        $correctIndex = (int) ($payload['correct_option_index'] ?? 0);
        $options = $payload['options'] ?? [];
        $correctText = '';

        if (isset($options[$correctIndex])) {
            $opt = $options[$correctIndex];
            $correctText = is_array($opt) ? ($opt['text'] ?? '') : (string) $opt;
        }

        return response()->json([
            'status' => 'success',
            'question_id' => $question->id,
            'correct_option_index' => $correctIndex,
            'correct_option_letter' => chr(65 + $correctIndex),
            'correct_option_text' => $correctText,
            'explanation' => $question->explanation ?? ($payload['explanation'] ?? null),
            'weak_status' => [
                'consecutive_correct' => $weak->consecutive_correct,
                'status' => $weak->status,
                'revealed_count' => $weak->revealed_count,
            ],
        ]);
    }

    /**
     * Record an option selection attempt. If practiced correctly without reveal,
     * increments consecutive correct count toward 2-strike Threshold Mastery.
     */
    public function recordAttempt(Request $request, QuestionBank $question): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'selected_index' => 'required|integer|min:0|max:10',
            'domain_id' => 'nullable|integer',
            'was_revealed' => 'nullable|boolean',
        ]);

        $payload = $question->payload ?? [];
        $correctIndex = (int) ($payload['correct_option_index'] ?? -1);
        $selectedIndex = (int) $validated['selected_index'];
        $wasRevealed = (bool) ($validated['was_revealed'] ?? false);

        $isCorrect = ($selectedIndex === $correctIndex) && ! $wasRevealed;

        $domainId = (int) ($validated['domain_id'] ?? 0);
        $domain = $domainId > 0
            ? Domain::find($domainId)
            : $question->modules()->first()?->domain;

        /** @var UserWeakQuestion|null $weak */
        $weak = UserWeakQuestion::where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->first();

        $isMastered = false;

        if ($weak) {
            $isMastered = $weak->recordAttempt($isCorrect);
        } elseif (! $isCorrect) {
            // New error: register as a weak question
            $weak = UserWeakQuestion::create([
                'user_id' => $user->id,
                'question_id' => $question->id,
                'domain_id' => $domain?->id,
                'consecutive_correct' => 0,
                'status' => 'active',
                'revealed_count' => $wasRevealed ? 1 : 0,
                'failed_count' => 1,
                'last_practiced_at' => now(),
            ]);
        }

        $weakStatus = null;
        if ($weak instanceof UserWeakQuestion) {
            $consecutiveCorrect = $weak->consecutive_correct;
            $weakStatus = [
                'consecutive_correct' => $weak->consecutive_correct,
                'status' => $weak->status,
                'failed_count' => $weak->failed_count,
            ];
        } else {
            $consecutiveCorrect = 1;
        }

        return response()->json([
            'status' => 'success',
            'question_id' => $question->id,
            'is_correct' => $isCorrect,
            'consecutive_correct' => $consecutiveCorrect,
            'is_mastered' => $isMastered,
            'weak_status' => $weakStatus,
        ]);
    }

    /**
     * Provide a practice dataset containing only active weak questions
     * for direct launch in McqPlayer.
     */
    public function practice(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = UserWeakQuestion::query()
            ->where('user_id', $user->id)
            ->active()
            ->with(['question']);

        $domainId = $request->integer('domain_id');
        if ($domainId > 0) {
            $query->where('domain_id', $domainId);
        }

        $weakRecords = $query->latest('last_practiced_at')->get();

        if ($weakRecords->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active weak questions to practice! You have mastered all flagged questions.',
            ], 404);
        }

        $questions = $weakRecords->map(function (UserWeakQuestion $w): array {
            $q = $w->question;
            $payload = $q->payload ?? [];

            // Zero-Knowledge Answer Key Scrubbing
            unset($payload['correct_option_index']);

            if (isset($payload['options']) && is_array($payload['options'])) {
                $payload['options'] = array_map(function ($option) {
                    if (is_array($option)) {
                        unset($option['is_correct']);

                        return $option['text'] ?? '';
                    }

                    return (string) $option;
                }, $payload['options']);
            }

            return [
                'id' => $q->id,
                'question_text' => $q->question_text,
                'question_type' => $q->question_type,
                'payload' => $payload,
                'explanation' => $q->explanation,
                'weak_meta' => [
                    'consecutive_correct' => $w->consecutive_correct,
                    'revealed_count' => $w->revealed_count,
                    'failed_count' => $w->failed_count,
                ],
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'module' => [
                'id' => 0,
                'title' => 'Weak MCQs Revision',
                'module_type' => 'preparation',
                'content_meta' => [
                    'description' => "Practicing {$questions->count()} Weak Questions",
                    'is_weak_mode' => true,
                    'duration_minutes' => max(10, (int) ceil($questions->count() * 1.5)),
                ],
            ],
            'questions' => $questions,
        ]);
    }
}
