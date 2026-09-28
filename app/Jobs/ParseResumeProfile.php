<?php

namespace App\Jobs;

use App\Models\AutomationProfile;
use App\Services\ResumeProfileParser;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ParseResumeProfile implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public int $uniqueFor = 600;

    public function __construct(public int $profileId) {}

    public function uniqueId(): string
    {
        return (string) $this->profileId;
    }

    /**
     * Execute the job.
     */
    public function handle(ResumeProfileParser $parser): void
    {
        $profile = AutomationProfile::query()->with('user')->find($this->profileId);

        if (! $profile || ! filled($profile->resume_text)) {
            return;
        }

        $profile->update([
            'resume_parse_status' => 'processing',
            'resume_parse_error' => null,
        ]);

        try {
            $resumeData = $parser->parse($profile);
            $profile->update([
                'resume_data' => $resumeData,
                'resume_parse_status' => 'ready',
                'resume_parse_error' => null,
                'resume_parsed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $profile->update([
                'resume_parse_status' => 'failed',
                'resume_parse_error' => mb_substr($exception->getMessage(), 0, 1000),
            ]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        AutomationProfile::query()->whereKey($this->profileId)->update([
            'resume_parse_status' => 'failed',
            'resume_parse_error' => mb_substr($exception->getMessage(), 0, 1000),
        ]);
    }
}
