<?php

namespace Tests\Feature;

use App\Jobs\ParseResumeProfile;
use App\Models\AutomationProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResumeProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vagaflow.gemini_key', 'test-key');
    }

    public function test_resume_text_is_parsed_into_an_encrypted_editable_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Viktor Lacerda',
            'email' => 'viktor@example.com',
        ]);
        $profile = AutomationProfile::query()->create([
            'user_id' => $user->id,
            'resume_text' => str_repeat('Engenheiro de Software com Laravel e certificação AWS. ', 20),
            'resume_parse_status' => 'pending',
        ]);
        $resume = $this->resumeData($user);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($resume)]]]]],
            ]),
        ]);

        ParseResumeProfile::dispatchSync($profile->id);

        $profile->refresh();
        $this->assertSame('ready', $profile->resume_parse_status);
        $this->assertSame('Certificações', $profile->resume_data['optional_sections'][0]['title']);
        $raw = DB::table('automation_profiles')->where('id', $profile->id)->first();
        $this->assertStringNotContainsString('Engenheiro de Software', $raw->resume_text);
        $this->assertStringNotContainsString('AWS Certified', $raw->resume_data);

        $this->actingAs($user)->getJson('/api/automation/resume/profile')
            ->assertOk()
            ->assertJsonPath('data.status', 'ready')
            ->assertJsonPath('data.profile.optional_sections.0.type', 'certifications');

        $edited = $resume;
        $edited['summary'] = 'Resumo revisado pelo usuário.';

        $this->putJson('/api/automation/resume/profile', $edited)
            ->assertOk()
            ->assertJsonPath('data.profile.summary', 'Resumo revisado pelo usuário.');

        $this->assertSame('Resumo revisado pelo usuário.', $profile->refresh()->resume_data['summary']);
    }

    public function test_unknown_optional_section_type_is_rejected(): void
    {
        $user = User::factory()->create();
        $resume = $this->resumeData($user);
        $resume['optional_sections'][0]['type'] = 'instructions';

        $this->actingAs($user)->putJson('/api/automation/resume/profile', $resume)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('optional_sections.0.type');
    }

    public function test_structured_profile_cannot_be_created_without_an_uploaded_resume(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/automation/resume/profile', $this->resumeData($user))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('resume');
    }

    /** @return array<string, mixed> */
    private function resumeData(User $user): array
    {
        return [
            'identity' => [
                'name' => $user->name,
                'title' => 'Engenheiro de Software',
                'location' => 'Brasília - DF',
                'phone' => null,
                'email' => $user->email,
                'links' => ['https://github.com/vla2005'],
            ],
            'summary' => 'Engenheiro de Software com experiência em Laravel.',
            'skills' => [['category' => 'Back-end', 'items' => ['PHP', 'Laravel']]],
            'experiences' => [[
                'organization' => 'Empresa Exemplo',
                'role' => 'Engenheiro de Software',
                'location' => 'Brasília - DF',
                'period' => '2024-2026',
                'bullets' => ['Desenvolveu APIs REST com Laravel.'],
            ]],
            'projects' => [],
            'education' => [[
                'institution' => 'Universidade Exemplo',
                'course' => 'Engenharia de Software',
                'location' => 'Brasília - DF',
                'period' => '2023-2026',
                'details' => [],
            ]],
            'optional_sections' => [[
                'type' => 'certifications',
                'title' => 'Certificações',
                'items' => [[
                    'heading' => 'AWS Certified Cloud Practitioner',
                    'subheading' => 'Amazon Web Services',
                    'period' => '2026',
                    'details' => ['Credencial validada.'],
                ]],
            ]],
        ];
    }
}
