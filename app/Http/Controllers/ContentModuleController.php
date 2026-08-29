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

        return response()->json([
            'status' => 'success',
            'module' => [
                'id' => $module->id,
                'title' => $module->title,
                'module_type' => $module->module_type,
                'content_meta' => $module->content_meta,
            ],
            'questions' => $module->questions->map(fn ($q) => [
                'id' => $q->id,
                'question_text' => $q->question_text,
                'question_type' => $q->question_type,
                'payload' => $q->payload,
                'explanation' => $q->explanation,
            ]),
        ]);
    }
}
