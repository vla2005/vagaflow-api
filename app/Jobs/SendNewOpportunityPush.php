<?php

namespace App\Jobs;

use App\Models\Job;
use App\Services\WebPushService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendNewOpportunityPush implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public int $jobId) {}

    public function uniqueId(): string
    {
        return (string) $this->jobId;
    }

    public function handle(WebPushService $push): void
    {
        $job = Job::query()->with('user.pushSubscriptions')->find($this->jobId);

        if (! $job || $job->analysis_state !== 'accepted' || $job->push_notified_at) {
            return;
        }

        $push->sendNewOpportunity($job);
    }
}
