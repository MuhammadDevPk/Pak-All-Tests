<?php

namespace App\Http\Controllers;

use App\Models\ContentModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class McqExecutionController extends Controller
{
    /**
     * Grade submitted MCQ responses and calculate score details.
     */
    public function __invoke(Request $request, ContentModule $module): JsonResponse
    {
        $request->validate([
            'answers' => ['required', 'array'],
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
            if ($userOptionIndex !== null) {
                $answeredCount++;
                if ($correctOptionIndex !== null && (int) $userOptionIndex === (int) $correctOptionIndex) {
                    $correctCount++;
                    $isCorrect = true;
                }
            }

            $results[$questionId] = [
                'correct' => $isCorrect,
                'correct_index' => $correctOptionIndex,
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
