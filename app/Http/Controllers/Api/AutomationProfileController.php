<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAutomationProfileRequest;
use App\Http\Resources\AutomationProfileResource;
use App\Services\AutomationProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutomationProfileController extends Controller
{
    public function __construct(private AutomationProfileService $profiles) {}

    public function show(Request $request): JsonResponse
    {
        return (new AutomationProfileResource($this->profiles->getOrCreate($request->user())))
            ->response()
            ->setStatusCode(200);
    }

    public function update(UpdateAutomationProfileRequest $request): JsonResponse
    {
        $profile = $this->profiles->update($request->user(), $request->validated());

        return (new AutomationProfileResource($profile))->response()->setStatusCode(200);
    }
}
