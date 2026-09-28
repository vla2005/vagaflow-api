<?php

namespace App\Services;

use App\Jobs\EvaluateOpportunityForUser;
use App\Models\AutomationProfile;
use App\Models\SourceJob;

class StoredOpportunityDispatcher
{
    public function __construct(private JobFilter $filter) {}

    /** @return array{profiles: int, considered: int, queued: int, filtered: int} */
    public function dispatch(string $email, int $limit = 200): array
    {
        $profiles = AutomationProfile::query()
            ->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('email', $email))
            ->get()
            ->filter(fn (AutomationProfile $profile): bool => $profile->isReady());

        $stats = [
            'profiles' => $profiles->count(),
            'considered' => 0,
            'queued' => 0,
            'filtered' => 0,
        ];

        foreach ($profiles as $profile) {
            $sourceJobs = SourceJob::query()
                ->where('is_closed', false)
                ->whereDoesntHave('opportunities', fn ($query) => $query->where('user_id', $profile->user_id))
                ->latest('last_seen_at')
                ->limit($limit)
                ->get();

            foreach ($sourceJobs as $sourceJob) {
                $stats['considered']++;

                if (! $this->filter->matchesProfile($sourceJob->payload, $profile)) {
                    $stats['filtered']++;

                    continue;
                }

                EvaluateOpportunityForUser::dispatch($profile->id, $sourceJob->id);
                $stats['queued']++;
            }
        }

        return $stats;
    }
}
