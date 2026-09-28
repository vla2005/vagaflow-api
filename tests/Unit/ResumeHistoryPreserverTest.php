<?php

namespace Tests\Unit;

use App\Services\ResumeHistoryPreserver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResumeHistoryPreserverTest extends TestCase
{
    #[Test]
    public function it_restores_history_omitted_by_the_model_without_losing_personalization(): void
    {
        $base = [
            'identity' => ['name' => 'Viktor', 'email' => 'real@example.com'],
            'experiences' => [
                ['organization' => 'Empresa A', 'role' => 'Dev', 'period' => '2024', 'bullets' => ['API Laravel']],
                ['organization' => 'Empresa B', 'role' => 'Estagiário', 'period' => '2023', 'bullets' => ['Testes']],
            ],
            'projects' => [
                ['name' => 'VagaFlow', 'period' => '2026', 'link' => null, 'bullets' => ['Automação com IA']],
                ['name' => 'NutriTreino', 'period' => '2026', 'link' => null, 'bullets' => ['Gestão nutricional']],
            ],
            'education' => [
                ['institution' => 'UCB', 'course' => 'Engenharia de Software', 'period' => '2023-2026', 'details' => []],
            ],
            'optional_sections' => [[
                'type' => 'certifications',
                'title' => 'Certificações',
                'items' => [['heading' => 'AWS', 'subheading' => null, 'period' => '2026', 'details' => []]],
            ]],
        ];
        $personalized = [
            'identity' => ['name' => 'Nome inventado', 'email' => 'inventado@example.com'],
            'experiences' => [
                ['organization' => 'Empresa A', 'role' => 'Dev', 'period' => '2024', 'bullets' => ['API Laravel priorizada para a vaga']],
                ['organization' => 'Empresa Inventada', 'role' => 'CTO', 'period' => '2025', 'bullets' => ['Fato inventado']],
            ],
            'projects' => [],
            'education' => [],
            'optional_sections' => [],
        ];

        $result = app(ResumeHistoryPreserver::class)->preserve($personalized, $base);

        $this->assertSame('Viktor', $result['identity']['name']);
        $this->assertSame('API Laravel priorizada para a vaga', $result['experiences'][0]['bullets'][0]);
        $this->assertCount(2, $result['experiences']);
        $this->assertSame(['Empresa A', 'Empresa B'], array_column($result['experiences'], 'organization'));
        $this->assertCount(2, $result['projects']);
        $this->assertCount(1, $result['education']);
        $this->assertCount(1, $result['optional_sections']);
    }
}
