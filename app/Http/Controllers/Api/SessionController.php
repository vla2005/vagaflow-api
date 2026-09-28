<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function __construct(private SessionService $sessions) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->sessions->register($request->validated());
        $request->session()->regenerate();

        return response()->json(['user' => new UserResource($user)], 201);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user() ? new UserResource($request->user()) : null,
            'csrf_token' => csrf_token(),
        ]);
    }

    public function store(LoginRequest $request): JsonResponse
    {
        $user = $this->sessions->authenticate($request->validated());
        $request->session()->regenerate();

        return response()->json(['user' => new UserResource($user)]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->sessions->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }
}
