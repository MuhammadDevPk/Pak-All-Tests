<?php

namespace App\Http\Controllers;

use App\Models\ContentModule;
use App\Services\SubscriptionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentModuleController extends Controller
{
    public function __construct(
        protected SubscriptionManager $subscriptionManager
    ) {}

    /**
     * Fetch the content module details and loaded questions.
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

        // Check if user has active subscription for the domain of this module
        if (! $this->subscriptionManager->hasActiveAccess($user, $module->domain)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Active subscription required for this domain.',
            ], 403);
        }

        // Eager load questions associated with this module
        $module->loadMissing(['questions' => function ($query) {
            $query->where('is_active', true);
        }]);

        $isMockTest = $module->module_type === 'mock_test';

        return response()->json([
            'status' => 'success',
            'module' => [
                'id' => $module->id,
                'title' => $module->title,
                'module_type' => $module->module_type,
                'dashboard_section' => $module->dashboard_section,
                'display_mode' => $module->resolveDisplayMode(),
                'content_meta' => $module->content_meta,
            ],
            'questions' => $module->questions->map(function ($q) use ($isMockTest) {
                $payload = $q->payload ?? [];

                // Scrub correct answer index and is_correct flags from active candidate payloads
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
                    'explanation' => $isMockTest ? null : $q->explanation,
                ];
            }),
        ]);
    }
}
