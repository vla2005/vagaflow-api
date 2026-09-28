<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexJobRequest;
use App\Http\Requests\UpdateJobStatusRequest;
use App\Http\Resources\JobResource;
use App\Http\Resources\JobSummaryResource;
use App\Models\Job;
use App\Services\JobService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class JobController extends Controller
{
    public function __construct(private JobService $jobs) {}

    public function index(IndexJobRequest $request): AnonymousResourceCollection
    {
        return JobSummaryResource::collection($this->jobs->paginate($request->user(), $request->validated()));
    }

    public function show(Request $request, Job $job): JobResource
    {
        return (new JobResource($this->jobs->view($request->user(), $job)))
            ->additional(['meta' => ['unread_count' => $this->jobs->unreadCount($request->user())]]);
    }

    public function updateStatus(UpdateJobStatusRequest $request, Job $job): JobResource
    {
        return new JobResource($this->jobs->updateStatus($request->user(), $job, $request->validated('status')));
    }

    public function downloadResume(Request $request, Job $job): Response
    {
        return $this->jobs->downloadResume($request->user(), $job);
    }
}
