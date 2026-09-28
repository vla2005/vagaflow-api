<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResumeRequest;
use App\Http\Requests\UpdateResumeProfileRequest;
use App\Http\Resources\ResumeProfileResource;
use App\Http\Resources\ResumeResource;
use App\Services\ResumeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResumeController extends Controller
{
    public function __construct(private ResumeService $resumes) {}

    public function store(StoreResumeRequest $request): JsonResponse
    {
        $profile = $this->resumes->store($request->user(), $request->file('resume'));

        return (new ResumeResource($profile))
            ->additional(['message' => 'Texto extraído com segurança. A organização do currículo foi enviada para processamento.'])
            ->response()
            ->setStatusCode(200);
    }

    public function showProfile(Request $request): ResumeProfileResource
    {
        return new ResumeProfileResource($this->resumes->getProfile($request->user()));
    }

    public function updateProfile(UpdateResumeProfileRequest $request): ResumeProfileResource
    {
        return new ResumeProfileResource(
            $this->resumes->updateProfile($request->user(), $request->validated()),
        );
    }

    public function retryParsing(Request $request): ResumeProfileResource
    {
        return new ResumeProfileResource($this->resumes->retryParsing($request->user()));
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->resumes->remove($request->user());

        return response()->json(['ok' => true]);
    }
}
