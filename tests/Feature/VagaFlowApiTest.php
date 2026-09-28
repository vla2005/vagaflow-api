<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VagaFlowApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeaders([
            'Origin' => 'http://127.0.0.1:5173',
            'Referer' => 'http://127.0.0.1:5173/',
        ]);
    }

    private function sampleJob(): array
    {
        return [
            'titulo_vaga' => 'Desenvolvedor Laravel',
            'nome_empresa' => 'Exemplo',
            'link_vaga' => 'https://example.com/jobs/123',
            'local' => 'Brasília',
            'forma_trabalho' => 'Remoto',
            'descricao_vaga' => 'Construção e manutenção de APIs para produtos digitais.',
            'requisitos_tecnicos' => 'PHP; Laravel; APIs REST',
            'requisitos_desejaveis' => 'Docker; GitHub Actions',
            'tipo_contrato' => 'CLT',
            'plataforma' => 'LinkedIn',
            'vaga_easy_apply' => true,
            'analise_ia' => [
                'adequada' => true,
                'pontuacao_adequacao' => 91,
                'resumo' => 'Boa aderência.',
                'motivo' => 'Experiência alinhada à stack principal.',
                'pontos_fortes' => ['Laravel em produção', 'APIs REST'],
                'pontos_atencao' => ['Aprofundar Docker'],
                'sugestao_preparacao' => 'Revise exemplos de APIs já entregues.',
            ],
        ];
    }

    public function test_jobs_are_private_and_status_changes_are_preserved(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $job = Job::create([
            'user_id' => $owner->id,
            'fingerprint' => hash('sha256', 'example'),
            'level' => 'junior',
            'title' => 'Desenvolvedor Laravel',
            'score' => 91,
            'payload' => $this->sampleJob(),
        ]);

        $this->getJson('/api/jobs')->assertUnauthorized();
        $this->actingAs($other)->getJson('/api/jobs')->assertJsonCount(0, 'data');
        $this->getJson("/api/jobs/{$job->id}")->assertNotFound();

        $this->actingAs($owner)->getJson('/api/jobs?level=junior&min_score=90')
            ->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/jobs/{$job->id}")
            ->assertOk()
            ->assertJsonPath('data.description', 'Construção e manutenção de APIs para produtos digitais.')
            ->assertJsonPath('data.requirements.0', 'PHP')
            ->assertJsonPath('data.technologies.1', 'Laravel')
            ->assertJsonPath('data.analysis.reason', 'Experiência alinhada à stack principal.')
            ->assertJsonPath('data.analysis.strengths.0', 'Laravel em produção')
            ->assertJsonPath('data.easy_apply', true);
        $this->patchJson("/api/jobs/{$job->id}/status", ['status' => 'applied'])
            ->assertOk()->assertJsonPath('data.status', 'applied');
        $this->getJson('/api/jobs?status=applied')->assertJsonCount(1, 'data');
        $this->patchJson("/api/jobs/{$job->id}/status", ['status' => 'interview'])
            ->assertOk()->assertJsonPath('data.status', 'interview');
        $this->getJson('/api/jobs?status=interview')->assertJsonCount(1, 'data');

        $this->patchJson("/api/jobs/{$job->id}/status", ['status' => 'rejected'])
            ->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->getJson('/api/jobs?status=rejected')->assertJsonCount(1, 'data');
        $this->patchJson("/api/jobs/{$job->id}/status", ['status' => 'invalid'])
            ->assertUnprocessable();
        $this->patchJson("/api/jobs/{$job->id}/status", ['status' => 'ignored'])->assertOk();
        $this->getJson('/api/jobs')->assertJsonCount(0, 'data');
        $this->getJson('/api/jobs?status=ignored')->assertJsonCount(1, 'data');

        $job->update(['analysis_state' => 'rejected']);
        $this->getJson('/api/jobs')->assertJsonCount(0, 'data');
        $this->getJson("/api/jobs/{$job->id}")->assertNotFound();
    }

    public function test_session_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $this->get('/sanctum/csrf-cookie')->assertNoContent();

        $this->postJson('/api/session', [
            'email' => $user->email,
            'password' => 'wrong',
        ])->assertUnprocessable();

        $this->postJson('/api/session', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertOk()->assertJsonPath('user.id', $user->id);

        $this->getJson('/api/session')->assertJsonPath('user.id', $user->id);
        $this->deleteJson('/api/session')->assertOk();
        $this->getJson('/api/session')->assertJsonPath('user', null);
    }

    public function test_session_endpoint_starts_a_session_without_browser_origin_headers(): void
    {
        $this->flushHeaders();

        $this->getJson('/api/session')
            ->assertOk()
            ->assertJsonPath('user', null)
            ->assertJsonStructure(['csrf_token']);
    }

    public function test_registration_requires_only_name_email_and_confirmed_password(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Viktor',
            'email' => 'viktor@example.com',
            'password' => 'senha-curta',
            'password_confirmation' => 'diferente',
        ])->assertUnprocessable()->assertJsonValidationErrors(['password']);

        $this->postJson('/api/register', [
            'name' => 'Viktor',
            'email' => 'viktor@example.com',
            'password' => 'uma-senha-segura-2026',
            'password_confirmation' => 'uma-senha-segura-2026',
        ])->assertCreated()->assertJsonPath('user.name', 'Viktor');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'viktor@example.com']);
        $storedPassword = User::query()->where('email', 'viktor@example.com')->value('password');
        $this->assertNotSame('uma-senha-segura-2026', $storedPassword);
        $this->assertTrue(Hash::check('uma-senha-segura-2026', $storedPassword));
        $this->getJson('/api/jobs')->assertOk()->assertJsonCount(0, 'data');
    }
}
