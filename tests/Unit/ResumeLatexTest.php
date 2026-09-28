<?php

namespace Tests\Unit;

use App\Services\ResumeLatex;
use Tests\TestCase;

class ResumeLatexTest extends TestCase
{
    public function test_it_renders_real_a4_and_dynamic_sections_with_escaped_content(): void
    {
        $source = app(ResumeLatex::class)->render([
            'identity' => [
                'name' => 'Viktor & Lacerda',
                'title' => 'Engenheiro de Software',
                'location' => 'Brasília - DF',
                'phone' => null,
                'email' => 'viktor@example.com',
                'links' => ['github.com/vla_2005'],
            ],
            'summary' => 'APIs com 20% menos latência.',
            'skills' => [['category' => 'Back-end', 'items' => ['PHP', 'Laravel']]],
            'experiences' => [],
            'projects' => [],
            'education' => [],
            'optional_sections' => [[
                'type' => 'certifications',
                'title' => 'Certificações',
                'items' => [[
                    'heading' => 'AWS Certified',
                    'subheading' => 'Amazon Web Services',
                    'period' => '2026',
                    'details' => [],
                ]],
            ]],
        ]);

        $this->assertStringContainsString('\\documentclass[a4paper,10pt]{article}', $source);
        $this->assertStringContainsString('top=0.9cm', $source);
        $this->assertStringContainsString('\\fontsize{8.8pt}{10.5pt}\\selectfont', $source);
        $this->assertStringContainsString('\\titlespacing*{\\section}{0pt}{7pt}{3pt}', $source);
        $this->assertStringContainsString('\\titlespacing*{\\subsection}{0pt}{4pt}{1pt}', $source);
        $this->assertStringContainsString('itemsep=0.8pt', $source);
        $this->assertStringContainsString('topsep=2pt', $source);
        $this->assertStringContainsString('\\section{Certificações}', $source);
        $this->assertStringContainsString('Viktor \\& Lacerda', $source);
        $this->assertStringContainsString('20\\%', $source);
        $this->assertStringContainsString('vla\\_2005', $source);
        $this->assertStringNotContainsString('paperheight=350mm', $source);
    }
}
