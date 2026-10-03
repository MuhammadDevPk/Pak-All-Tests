<?php

namespace App\Http\Controllers;

use App\Models\ContentModule;
use App\Models\UserWeakQuestion;
use App\Services\SubscriptionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class McqExecutionController extends Controller
{
    public function __construct(
        protected SubscriptionManager $subscriptionManager
    ) {}

    /**
     * Grade submitted MCQ responses and calculate score details.
     */
    public function __invoke(Request $request, ContentModule $module): JsonResponse
    {
        $user = $request->user();

        if (! $module->is_active || ! $module->domain()->where('is_active', true)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This module is not currently available.',
            ], 404);
        }

        if (! $this->subscriptionManager->hasActiveAccess($user, $module->domain)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Active subscription required to submit test responses.',
            ], 403);
        }

        $request->validate([
            'answers' => ['present', 'array'],
            'revealed' => ['nullable', 'array'],
        ]);

        $userAnswers = $request->input('answers', []);
        $revealedMap = array_flip(array_map('intval', (array) $request->input('revealed', [])));

        // Ensure questions relationship is loaded (prevents N+1 in future refactors)
        $questions = $module->loadMissing('questions')->questions;

        $totalQuestions = $questions->count();
        $correctCount = 0;
        $answeredCount = 0;
        $results = [];

        foreach ($questions as $question) {
            $questionId = (int) $question->id;
            $userOptionIndex = $userAnswers[$questionId] ?? null;
            $correctOptionIndex = $question->payload['correct_option_index'] ?? null;
            $wasRevealed = isset($revealedMap[$questionId]);

            $isCorrect = false;
            if ($userOptionIndex !== null && is_numeric($userOptionIndex)) {
                $answeredCount++;
                if (
                    ! $wasRevealed &&
                    $correctOptionIndex !== null &&
                    is_numeric($correctOptionIndex) &&
                    (int) $userOptionIndex === (int) $correctOptionIndex
                ) {
                    $correctCount++;
                    $isCorrect = true;
                }
            }

            // Sync with UserWeakQuestion table
            $weak = UserWeakQuestion::where('user_id', $user->id)
                ->where('question_id', $questionId)
                ->first();

            if ($weak) {
                $weak->recordAttempt($isCorrect);
            } elseif (! $isCorrect && ($userOptionIndex !== null || $wasRevealed)) {
                UserWeakQuestion::create([
                    'user_id' => $user->id,
                    'question_id' => $questionId,
                    'domain_id' => $module->domain_id,
                    'consecutive_correct' => 0,
                    'status' => 'active',
                    'revealed_count' => $wasRevealed ? 1 : 0,
                    'failed_count' => ($userOptionIndex !== null) ? 1 : 0,
                    'last_practiced_at' => now(),
                ]);
            }

            $results[$questionId] = [
                'correct' => $isCorrect,
                'revealed' => $wasRevealed,
                'correct_index' => is_numeric($correctOptionIndex) ? (int) $correctOptionIndex : null,
                'explanation' => $question->explanation,
            ];
        }

        $percentage = $totalQuestions > 0 ? (int) round(($correctCount / $totalQuestions) * 100) : 0;
        $passingScore = $module->content_meta['passing_score'] ?? 60;
        $isPassed = $percentage >= $passingScore;

        return response()->json([
            'status' => 'success',
            'summary' => [
                'total_questions' => $totalQuestions,
                'answered_count' => $answeredCount,
                'correct_count' => $correctCount,
                'percentage' => $percentage,
                'passing_score' => $passingScore,
                'is_passed' => $isPassed,
            ],
            'questions' => $results,
        ]);
    }
}
