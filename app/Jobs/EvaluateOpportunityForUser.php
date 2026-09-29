<?php

namespace App\Jobs;

use App\Mail\OpportunityFound;
use App\Models\AutomationProfile;
use App\Models\Job;
use App\Models\SourceJob;
use App\Services\JobFilter;
use App\Services\OpportunityAnalyzer;
use App\Services\ResumeLatex;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class EvaluateOpportunityForUser implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 180;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [60, 180, 600, 1800];
    }

    public function __construct(
        public int $profileId,
        public int $sourceJobId,
    ) {}

    public function uniqueId(): string
    {
        return $this->profileId.':'.$this->sourceJobId;
    }

    public function handle(JobFilter $filter, OpportunityAnalyzer $analyzer, ResumeLatex $latex): void
    {
        $profile = AutomationProfile::query()->with('user')->find($this->profileId);
        $sourceJob = SourceJob::query()->find($this->sourceJobId);

        if (! $profile || ! $sourceJob || ! $profile->is_active || ! $profile->isReady()) {
            return;
        }

        $existing = Job::query()
            ->where('user_id', $profile->user_id)
            ->where('source_job_id', $sourceJob->id)
            ->first();

        if ($existing) {
            if ($existing->analysis_state === 'accepted' && (! $existing->sent_at || ! $existing->push_notified_at)) {
                $this->deliver($existing);
            }

            return;
        }

        $details = $sourceJob->payload;
        if (! $filter->matchesProfile($details, $profile)) {
            return;
        }

        $analysis = $analyzer->analyze($details, $profile);
        $resumeLatex = $analysis['adequada']
            ? $latex->render($analysis['curriculo_personalizado'])
            : null;
        unset($analysis['curriculo_personalizado']);
        $details['analise_ia'] = $analysis;
        $accepted = $analysis['adequada'];

        $job = Job::query()->create([
            'user_id' => $profile->user_id,
            'source_job_id' => $sourceJob->id,
            'fingerprint' => hash('sha256', $sourceJob->source.'|'.$sourceJob->source_key),
            'level' => $sourceJob->level,
            'title' => $sourceJob->title,
            'company' => $sourceJob->company,
            'location' => $sourceJob->location,
            'work_mode' => $sourceJob->work_mode,
            'url' => $sourceJob->url,
            'score' => $analysis['pontuacao_adequacao'],
            'analysis_state' => $accepted ? 'accepted' : 'rejected',
            'resume_latex' => $resumeLatex,
            'payload' => $details,
            'discovered_at' => now(),
        ]);

        if ($accepted) {
            $this->deliver($job);
        }
    }

    private function deliver(Job $job): void
    {
        if (! $job->push_notified_at) {
            SendNewOpportunityPush::dispatch($job->id);
        }

        if (! $job->sent_at) {
            Mail::to($job->user->email)->send(new OpportunityFound($job));
            $job->update(['sent_at' => now()]);
        }
    }
}
