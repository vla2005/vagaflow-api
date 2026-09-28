<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyPushSubscriptionRequest;
use App\Http\Requests\StorePushSubscriptionRequest;
use App\Services\PushSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function __construct(private PushSubscriptionService $subscriptions) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->subscriptions->status($request->user())]);
    }

    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $this->subscriptions->store($request->user(), $request->validated(), $request->userAgent());

        return response()->json([
            'message' => 'Notificações ativadas neste dispositivo.',
            'data' => $this->subscriptions->status($request->user()),
        ]);
    }

    public function destroy(DestroyPushSubscriptionRequest $request): JsonResponse
    {
        $this->subscriptions->remove($request->user(), $request->validated('endpoint'));

        return response()->json([
            'message' => 'Notificações desativadas neste dispositivo.',
            'data' => $this->subscriptions->status($request->user()),
        ]);
    }
}
