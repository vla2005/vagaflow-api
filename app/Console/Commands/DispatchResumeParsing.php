<?php

namespace App\Console\Commands;

use App\Jobs\ParseResumeProfile;
use App\Models\AutomationProfile;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('vagaflow:dispatch-resume-parsing')]
#[Description('Agenda a interpretação dos currículos pendentes')]
class DispatchResumeParsing extends Command
{
    public function handle(): int
    {
        $profileIds = AutomationProfile::query()
            ->whereNotNull('resume_text')
            ->where('resume_parse_status', 'pending')
            ->pluck('id');

        $profileIds->each(fn (int $profileId) => ParseResumeProfile::dispatch($profileId));
        $this->info("{$profileIds->count()} currículo(s) enviado(s) para interpretação.");

        return self::SUCCESS;
    }
}
