<?php

namespace App\Http\Controllers;

use App\Models\ContentModule;
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
        ]);

        $userAnswers = $request->input('answers');

        // Ensure questions relationship is loaded (prevents N+1 in future refactors)
        $questions = $module->loadMissing('questions')->questions;

        $totalQuestions = $questions->count();
        $correctCount = 0;
        $answeredCount = 0;
        $results = [];

        foreach ($questions as $question) {
            $questionId = $question->id;
            $userOptionIndex = $userAnswers[$questionId] ?? null;
            $correctOptionIndex = $question->payload['correct_option_index'] ?? null;

            $isCorrect = false;
            if ($userOptionIndex !== null && is_numeric($userOptionIndex)) {
                $answeredCount++;
                if (
                    $correctOptionIndex !== null &&
                    is_numeric($correctOptionIndex) &&
                    (int) $userOptionIndex === (int) $correctOptionIndex
                ) {
                    $correctCount++;
                    $isCorrect = true;
                }
            }

            $results[$questionId] = [
                'correct' => $isCorrect,
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
