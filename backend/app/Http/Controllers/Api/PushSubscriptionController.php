<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function __construct(protected WebPushService $webPushService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $subscriptions = $this->webPushService->subscriptionsForUser($request->user());

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
            'meta' => [
                'subscribed' => $subscriptions->isNotEmpty(),
                'supported' => $this->webPushService->isEnabled(),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['nullable', 'string', 'max:32'],
            'user_agent' => ['nullable', 'string', 'max:255'],
        ]);

        $subscription = $this->webPushService->registerSubscription($request->user(), [
            'endpoint' => $validated['endpoint'],
            'keys' => $validated['keys'],
            'content_encoding' => $validated['content_encoding'] ?? null,
            'user_agent' => $validated['user_agent'] ?? $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $subscription,
            'message' => 'Push subscription saved successfully',
            'meta' => [
                'supported' => $this->webPushService->isEnabled(),
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:2048'],
        ]);

        $deleted = $this->webPushService->removeSubscription($request->user(), $validated['endpoint']);

        return response()->json([
            'success' => true,
            'data' => [
                'deleted' => $deleted,
            ],
            'message' => 'Push subscription removed successfully',
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
