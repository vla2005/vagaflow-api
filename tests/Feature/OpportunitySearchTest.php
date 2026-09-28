<?php

namespace Tests\Feature;

use App\Mail\OpportunityFound;
use App\Models\AutomationProfile;
use App\Models\Job;
use App\Models\SourceJob;
use App\Models\User;
use App\Services\LatexCompiler;
use App\Services\OpportunitySearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class OpportunitySearchTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $latexCompiler;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vagaflow.gemini_key', 'test-key');
        config()->set('queue.default', 'sync');
        Mail::fake();
        Storage::fake('local');
        $this->latexCompiler = $this->mock(LatexCompiler::class, function (MockInterface $mock): void {
            $mock->shouldReceive('compile')->zeroOrMoreTimes()->andReturn('%PDF-1.4 test');
        });
    }

    private function createActiveProfile(string $email = 'candidate@example.com'): User
    {
        $user = User::factory()->create(['email' => $email]);
        AutomationProfile::query()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'job_titles' => ['Desenvolvedor'],
            'seniorities' => ['junior'],
            'technologies' => ['Laravel'],
            'excluded_keywords' => ['suporte'],
            'work_modes' => ['remoto', 'hibrido'],
            'locations' => ['Brasília'],
            'minimum_score' => 80,
            'resume_text' => str_repeat('Experiência profissional com PHP Laravel e APIs REST. ', 20),
            'resume_data' => $this->resumeData($email),
            'resume_parse_status' => 'ready',
            'resume_updated_at' => now(),
            'resume_parsed_at' => now(),
        ]);

        return $user;
    }

    private function fakeSource(bool $approved = true): void
    {
        $personalizedResume = $this->resumeData();

        Http::fake([
            'meupadrinho.com.br/api/vagas?niveis=junior&page=0' => Http::response([
                'vagas' => [['nano_id' => 'abc123', 'nivel' => 'junior', 'titulo_vaga' => 'Desenvolvedor PHP']],
                'tem_proxima_pagina' => false,
            ]),
            'meupadrinho.com.br/api/vagas/abc123/tecnologias' => Http::response(['tecnologias' => ['PHP', 'Laravel']]),
            'meupadrinho.com.br/api/vagas/abc123' => Http::response([
                'nivel' => 'junior', 'titulo_vaga' => 'Desenvolvedor PHP Laravel',
                'nome_empresa' => 'Empresa Exemplo', 'local' => 'Brasília - DF',
                'forma_trabalho' => 'hibrido', 'link_vaga' => 'https://example.com/vaga/abc123',
                'descricao_vaga' => 'Desenvolvimento de APIs REST com PHP e Laravel',
            ]),
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    'adequada' => $approved,
                    'pontuacao_adequacao' => $approved ? 90 : 40,
                    'motivo' => 'Experiência compatível.',
                    'resumo' => 'Vaga PHP.',
                    'pontos_fortes' => ['Laravel'],
                    'pontos_atencao' => [],
                    'sugestao_preparacao' => 'Revisar APIs.',
                    'curriculo_personalizado' => $approved ? $personalizedResume : null,
                ])]]]]],
            ]),
        ]);
    }

    public function test_source_is_collected_once_and_evaluated_for_each_active_user(): void
    {
        $firstUser = $this->createActiveProfile('first@example.com');
        $secondUser = $this->createActiveProfile('second@example.com');
        $this->fakeSource();

        $stats = app(OpportunitySearch::class)->run('junior');

        $this->assertSame(1, $stats['collected']);
        $this->assertSame(2, $stats['queued']);
        $this->assertDatabaseCount('source_jobs', 1);
        $this->assertDatabaseCount('opportunities', 2);
        $this->assertSame(1, SourceJob::query()->count());
        $job = Job::query()->where('user_id', $firstUser->id)->firstOrFail();
        $this->assertNotNull($job->sent_at);
        $this->assertNotNull(Job::query()->where('user_id', $secondUser->id)->firstOrFail()->sent_at);
        $this->assertStringContainsString('\\documentclass[a4paper,10pt]{article}', $job->resume_latex);
        $this->assertArrayNotHasKey('curriculo_personalizado', $job->payload['analise_ia']);
        $this->assertStringNotContainsString(
            '\\documentclass',
            DB::table('opportunities')->where('id', $job->id)->value('resume_latex'),
        );
        Mail::assertSent(OpportunityFound::class, 2);
        Mail::assertSent(OpportunityFound::class, fn (OpportunityFound $mail): bool => $mail->attachments() === []);
        $this->latexCompiler->shouldNotHaveReceived('compile');
        $this->assertSame([], Storage::disk('local')->allFiles());

        $this->actingAs($firstUser)->get("/api/jobs/{$job->id}/resume")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('cache-control', 'max-age=0, no-store, private')
            ->assertContent('%PDF-1.4 test');
        $this->latexCompiler->shouldHaveReceived('compile')->once();

        app(OpportunitySearch::class)->run('junior');
        $this->assertDatabaseCount('source_jobs', 1);
        $this->assertDatabaseCount('opportunities', 2);
        Mail::assertSent(OpportunityFound::class, 2);
    }

    public function test_profile_filters_run_before_gemini(): void
    {
        $user = $this->createActiveProfile();
        $user->automationProfile->update(['excluded_keywords' => ['Laravel']]);
        $this->fakeSource();

        app(OpportunitySearch::class)->run('junior');

        $this->assertDatabaseCount('source_jobs', 1);
        $this->assertDatabaseCount('opportunities', 0);
        Mail::assertNothingSent();
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'generativelanguage.googleapis.com'));
    }

    public function test_rejected_job_is_remembered_but_not_exposed(): void
    {
        $user = $this->createActiveProfile();
        $this->fakeSource(false);

        app(OpportunitySearch::class)->run('junior');

        $job = Job::query()->firstOrFail();
        $this->assertSame('rejected', $job->analysis_state);
        Mail::assertNothingSent();
        $this->actingAs($user)->getJson('/api/jobs')->assertJsonCount(0, 'data');
    }

    public function test_source_jobs_are_collected_even_without_active_users(): void
    {
        $this->fakeSource();

        $stats = app(OpportunitySearch::class)->run('junior');

        $this->assertSame(1, $stats['collected']);
        $this->assertSame(0, $stats['queued']);
        $this->assertDatabaseCount('source_jobs', 1);
        $this->assertDatabaseCount('opportunities', 0);
    }

    public function test_discovery_paginates_and_does_not_refetch_known_job_details(): void
    {
        config()->set('vagaflow.max_source_jobs_per_level', 20);

        Http::fake([
            'meupadrinho.com.br/api/vagas?niveis=junior&page=0' => Http::response([
                'vagas' => [[
                    'nano_id' => 'first-job',
                    'nivel' => 'junior',
                    'titulo_vaga' => 'Desenvolvedor Backend',
                    'vaga_verificada_por_ia' => false,
                ]],
                'tem_proxima_pagina' => true,
            ]),
            'meupadrinho.com.br/api/vagas?niveis=junior&page=1' => Http::response([
                'vagas' => [[
                    'nano_id' => 'second-job',
                    'nivel' => 'junior',
                    'titulo_vaga' => 'Desenvolvedor Full Stack',
                    'vaga_verificada_por_ia' => false,
                ]],
                'tem_proxima_pagina' => false,
            ]),
            'meupadrinho.com.br/api/vagas/first-job' => Http::response([
                'nivel' => 'junior', 'titulo_vaga' => 'Desenvolvedor Backend',
            ]),
            'meupadrinho.com.br/api/vagas/second-job' => Http::response([
                'nivel' => 'junior', 'titulo_vaga' => 'Desenvolvedor Full Stack',
            ]),
        ]);

        $firstRun = app(OpportunitySearch::class)->run('junior');

        $this->assertSame(2, $firstRun['pages']);
        $this->assertSame(2, $firstRun['collected']);
        $this->assertDatabaseCount('source_jobs', 2);
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/tecnologias'));

        $secondRun = app(OpportunitySearch::class)->run('junior');

        $this->assertSame(1, $secondRun['pages']);
        $this->assertSame(1, $secondRun['reused']);
        Http::assertSentCount(5);
    }

    public function test_source_metadata_filters_run_before_gemini(): void
    {
        $user = $this->createActiveProfile();
        $user->automationProfile->update([
            'platforms' => ['gupy'],
            'employment_types' => ['clt'],
            'easy_apply_only' => true,
        ]);

        Http::fake([
            'meupadrinho.com.br/api/vagas?niveis=junior&page=0' => Http::response([
                'vagas' => [[
                    'nano_id' => 'filtered-job',
                    'nivel' => 'junior',
                    'titulo_vaga' => 'Desenvolvedor PHP Laravel',
                    'plataforma' => 'linkedin',
                    'tipo_contratacao' => 'pj',
                    'vaga_easy_apply' => false,
                    'vaga_verificada_por_ia' => false,
                ]],
                'tem_proxima_pagina' => false,
            ]),
            'meupadrinho.com.br/api/vagas/filtered-job' => Http::response([
                'nivel' => 'junior',
                'titulo_vaga' => 'Desenvolvedor PHP Laravel',
                'descricao_vaga' => 'Desenvolvimento de APIs REST com Laravel',
                'forma_trabalho' => 'remoto',
                'plataforma' => 'linkedin',
                'tipo_contratacao' => 'pj',
                'vaga_easy_apply' => false,
                'vaga_verificada_por_ia' => false,
            ]),
        ]);

        app(OpportunitySearch::class)->run('junior');

        $sourceJob = SourceJob::query()->firstOrFail();
        $this->assertSame('linkedin', $sourceJob->platform);
        $this->assertSame('pj', $sourceJob->employment_type);
        $this->assertFalse($sourceJob->is_easy_apply);
        $this->assertDatabaseCount('opportunities', 0);
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'generativelanguage.googleapis.com'));
    }

    /** @return array<string, mixed> */
    private function resumeData(string $email = 'candidate@example.com'): array
    {
        return [
            'identity' => [
                'name' => 'Candidato Teste',
                'title' => 'Desenvolvedor',
                'location' => 'Brasília - DF',
                'phone' => null,
                'email' => $email,
                'links' => ['https://github.com/example'],
            ],
            'summary' => 'Desenvolvedor Laravel com experiência em APIs.',
            'skills' => [['category' => 'Back-end', 'items' => ['PHP', 'Laravel']]],
            'experiences' => [[
                'organization' => 'Empresa',
                'role' => 'Desenvolvedor',
                'location' => 'Brasília - DF',
                'period' => '2024-2026',
                'bullets' => ['Desenvolvimento de APIs REST com Laravel.'],
            ]],
            'projects' => [[
                'name' => 'Projeto Web',
                'period' => '2026',
                'link' => null,
                'bullets' => ['Aplicação criada com Laravel e React.'],
            ]],
            'education' => [[
                'institution' => 'Universidade Exemplo',
                'course' => 'Engenharia de Software',
                'location' => 'Brasília - DF',
                'period' => '2023-2026',
                'details' => [],
            ]],
            'optional_sections' => [],
        ];
    }
}
