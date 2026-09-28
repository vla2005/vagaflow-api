<?php

namespace Tests\Feature;

use App\Jobs\ParseResumeProfile;
use App\Models\AutomationProfile;
use App\Models\User;
use Dompdf\Dompdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AutomationProfileTest extends TestCase
{
    use RefreshDatabase;

    private function configuration(bool $active = false): array
    {
        return [
            'is_active' => $active,
            'job_titles' => ['Desenvolvedor Backend'],
            'seniorities' => ['junior', 'pleno'],
            'technologies' => ['Laravel', 'React'],
            'excluded_keywords' => ['suporte'],
            'work_modes' => ['remoto', 'hibrido'],
            'locations' => ['Brasília'],
            'minimum_score' => 78,
        ];
    }

    private function pdfWithText(string $text): UploadedFile
    {
        $pdf = new Dompdf;
        $pdf->loadHtml('<html><body><p>'.e($text).'</p></body></html>', 'UTF-8');
        $pdf->render();

        return UploadedFile::fake()->createWithContent('curriculo.pdf', $pdf->output());
    }

    public function test_profile_is_private_and_requires_a_resume_before_activation(): void
    {
        $user = User::factory()->create();

        $this->getJson('/api/automation')->assertUnauthorized();
        $this->actingAs($user)->getJson('/api/automation')
            ->assertOk()->assertJsonPath('data.has_resume', false);

        $this->putJson('/api/automation', $this->configuration(true))
            ->assertUnprocessable()->assertJsonValidationErrors('is_active');

        $this->putJson('/api/automation', $this->configuration())
            ->assertOk()->assertJsonPath('data.minimum_score', 78);
    }

    public function test_text_pdf_is_extracted_and_only_encrypted_text_is_persisted(): void
    {
        Storage::fake('local');
        Queue::fake([ParseResumeProfile::class]);
        $user = User::factory()->create();
        $resume = str_repeat('Desenvolvedor de software com experiência em Laravel, APIs REST e bancos de dados. ', 12);

        $response = $this->actingAs($user)->post('/api/automation/resume', [
            'resume' => $this->pdfWithText($resume),
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('data.has_resume', true)
            ->assertJsonPath('data.resume_parse_status', 'pending')
            ->assertJsonPath('message', 'Texto extraído com segurança. A organização do currículo foi enviada para processamento.');

        $profile = AutomationProfile::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertStringContainsString('Laravel', $profile->resume_text);
        $this->assertStringNotContainsString('Laravel', DB::table('automation_profiles')->where('id', $profile->id)->value('resume_text'));
        $this->assertSame([], Storage::disk('local')->allFiles());
        Queue::assertPushed(ParseResumeProfile::class, fn (ParseResumeProfile $job): bool => $job->profileId === $profile->id);

        $profile->update([
            'resume_data' => $this->resumeData($user),
            'resume_parse_status' => 'ready',
            'resume_parsed_at' => now(),
        ]);

        $this->putJson('/api/automation', $this->configuration(true))
            ->assertOk()->assertJsonPath('data.is_active', true);
    }

    public function test_pdf_without_extractable_text_and_non_pdf_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/api/automation/resume', [
            'resume' => UploadedFile::fake()->createWithContent('curriculo.txt', 'não é um PDF'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('resume');

        $this->post('/api/automation/resume', [
            'resume' => $this->pdfWithText('Currículo curto'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()
            ->assertJsonValidationErrors('resume')
            ->assertJsonPath('errors.resume.0', 'Este PDF não possui texto suficiente para extração. Envie um currículo com texto selecionável, não uma imagem escaneada.');
    }

    /** @return array<string, mixed> */
    private function resumeData(User $user): array
    {
        return [
            'identity' => [
                'name' => $user->name,
                'title' => 'Desenvolvedor de Software',
                'location' => 'Brasília - DF',
                'phone' => null,
                'email' => $user->email,
                'links' => [],
            ],
            'summary' => 'Desenvolvedor com experiência em Laravel e APIs REST.',
            'skills' => [['category' => 'Back-end', 'items' => ['PHP', 'Laravel']]],
            'experiences' => [],
            'projects' => [],
            'education' => [],
            'optional_sections' => [],
        ];
    }
}
