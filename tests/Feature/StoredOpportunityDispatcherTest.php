<?php

namespace Tests\Feature;

use App\Jobs\EvaluateOpportunityForUser;
use App\Models\AutomationProfile;
use App\Models\SourceJob;
use App\Models\User;
use App\Support\OpportunityTaxonomy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StoredOpportunityDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_queues_only_matching_stored_jobs(): void
    {
        Queue::fake();
        $user = User::factory()->create(['email' => 'candidate@example.com']);
        $profile = AutomationProfile::query()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'job_titles' => ['Developer'],
            'seniorities' => ['junior'],
            'technologies' => [],
            'excluded_keywords' => ['suporte'],
            'work_modes' => ['remoto'],
            'locations' => [],
            'resume_text' => str_repeat('Experiência com desenvolvimento de software. ', 20),
            'resume_data' => ['identity' => ['name' => 'Candidato']],
            'resume_parse_status' => 'ready',
        ]);
        $matching = $this->sourceJob('matching', 'Backend Developer PHP');
        $this->sourceJob('filtered', 'Analista de suporte');

        $this->artisan('vagaflow:reevaluate', [
            'email' => $user->email,
            '--limit' => 20,
        ])->assertSuccessful();

        Queue::assertPushed(EvaluateOpportunityForUser::class, 1);
        Queue::assertPushed(
            EvaluateOpportunityForUser::class,
            fn (EvaluateOpportunityForUser $job): bool => $job->profileId === $profile->id
                && $job->sourceJobId === $matching->id,
        );
    }

    public function test_selecting_every_contract_and_platform_accepts_missing_metadata(): void
    {
        Queue::fake();
        $user = User::factory()->create(['email' => 'all-options@example.com']);
        AutomationProfile::query()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'job_titles' => ['Developer'],
            'areas' => [],
            'seniorities' => ['junior'],
            'technologies' => [],
            'excluded_keywords' => [],
            'work_modes' => ['remoto', 'hibrido', 'presencial'],
            'platforms' => OpportunityTaxonomy::PLATFORMS,
            'employment_types' => OpportunityTaxonomy::EMPLOYMENT_TYPES,
            'locations' => [],
            'resume_text' => str_repeat('Experiência com desenvolvimento de software. ', 20),
            'resume_data' => ['identity' => ['name' => 'Candidato']],
            'resume_parse_status' => 'ready',
        ]);
        $this->sourceJob('missing-metadata', 'Backend Developer');

        $this->artisan('vagaflow:reevaluate', ['email' => $user->email])->assertSuccessful();

        Queue::assertPushed(EvaluateOpportunityForUser::class, 1);
    }

    public function test_short_excluded_technology_only_matches_a_complete_term(): void
    {
        Queue::fake();
        $user = User::factory()->create(['email' => 'short-keyword@example.com']);
        AutomationProfile::query()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'job_titles' => ['Developer'],
            'seniorities' => ['junior'],
            'technologies' => [],
            'excluded_keywords' => ['C', 'Go'],
            'work_modes' => ['remoto'],
            'locations' => [],
            'resume_text' => str_repeat('Experiência com desenvolvimento de software. ', 20),
            'resume_data' => ['identity' => ['name' => 'Candidato']],
            'resume_parse_status' => 'ready',
        ]);
        $this->sourceJob('word-boundaries', 'Developer de aplicações');

        $this->artisan('vagaflow:reevaluate', ['email' => $user->email])->assertSuccessful();

        Queue::assertPushed(EvaluateOpportunityForUser::class, 1);
    }

    private function sourceJob(string $key, string $title): SourceJob
    {
        return SourceJob::query()->create([
            'source' => 'meu_padrinho',
            'source_key' => $key,
            'level' => 'junior',
            'title' => $title,
            'work_mode' => 'remoto',
            'payload' => [
                'nivel' => 'junior',
                'titulo_vaga' => $title,
                'forma_trabalho' => 'remoto',
                'descricao_vaga' => 'Desenvolvimento de aplicações web.',
            ],
            'last_seen_at' => now(),
        ]);
    }
}
