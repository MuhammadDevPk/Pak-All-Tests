<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\Ai\AiTutorService;
use App\Services\SubscriptionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiTutorController extends Controller
{
    public function __construct(
        protected AiTutorService $aiTutorService,
        protected SubscriptionManager $subscriptionManager
    ) {}

    /**
     * Retrieve candidate's conversation history for this domain.
     */
    public function history(Request $request, string $domainSlug): JsonResponse
    {
        $domain = Domain::where('slug', $domainSlug)->firstOrFail();
        $user = $request->user();

        if (! $this->subscriptionManager->hasActiveAccess($user, $domain)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Active subscription required to access the AI Exam Mentor.',
            ], 403);
        }

        $messages = $this->aiTutorService->getHistory($user, $domain);

        return response()->json([
            'status' => 'success',
            'domain' => [
                'id' => $domain->id,
                'name' => $domain->name,
                'slug' => $domain->slug,
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Query the Socratic AI Tutor with real-time SSE streaming or JSON fallback.
     */
    public function ask(Request $request, string $domainSlug): JsonResponse|StreamedResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:3000',
            'context' => 'nullable|array',
            'chat_history' => 'nullable|array',
            'stream' => 'nullable|boolean',
        ]);

        $domain = Domain::where('slug', $domainSlug)->firstOrFail();
        $user = $request->user();

        if (! $this->subscriptionManager->hasActiveAccess($user, $domain)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Active subscription required to access the AI Exam Mentor.',
            ], 403);
        }

        $message = trim($validated['message']);
        $context = $validated['context'] ?? [];
        $wantsStream = $request->boolean('stream', true) || str_contains((string) $request->header('Accept'), 'text/event-stream');

        // Streaming Response via Server-Sent Events (SSE)
        if ($wantsStream) {
            return response()->stream(function () use ($user, $domain, $message, $context) {
                // Disable output buffering for instant chunk flushing in web requests
                if (! app()->runningUnitTests()) {
                    while (ob_get_level() > 0) {
                        ob_end_flush();
                    }
                }

                $this->aiTutorService->ask(
                    $user,
                    $domain,
                    $message,
                    $context,
                    function (string $token) {
                        echo "event: chunk\n";
                        echo 'data: '.json_encode(['token' => $token, 'text' => $token])."\n\n";
                        if (! app()->runningUnitTests()) {
                            flush();
                        }
                    }
                );

                echo "event: done\n";
                echo "data: {}\n\n";
                echo "data: [DONE]\n\n";
                if (! app()->runningUnitTests()) {
                    flush();
                }
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache, no-transform',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        // Standard JSON Response Fallback
        $response = $this->aiTutorService->ask($user, $domain, $message, $context);

        return response()->json([
            'status' => 'success',
            'response' => $response,
        ]);
    }

    /**
     * Clear candidate's conversation history for this domain.
     */
    public function clear(Request $request, string $domainSlug): JsonResponse
    {
        $domain = Domain::where('slug', $domainSlug)->firstOrFail();
        $user = $request->user();

        if (! $this->subscriptionManager->hasActiveAccess($user, $domain)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Active subscription required to access the AI Exam Mentor.',
            ], 403);
        }

        $this->aiTutorService->clearHistory($user, $domain);

        return response()->json([
            'status' => 'success',
            'message' => 'Conversation history cleared successfully.',
        ]);
    }
}
