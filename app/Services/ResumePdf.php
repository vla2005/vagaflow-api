<?php

namespace App\Services;

use App\Models\Job;
use RuntimeException;

class ResumePdf
{
    public function __construct(
        private ResumeLatex $latex,
        private LatexCompiler $compiler,
    ) {}

    public function generate(Job $job): string
    {
        $source = $job->resume_latex;

        if (! filled($source)) {
            $resume = $job->payload['analise_ia']['curriculo_personalizado'] ?? null;

            if (! is_array($resume)) {
                throw new RuntimeException('Currículo personalizado não encontrado.');
            }

            $source = $this->latex->render($resume);
            $job->update(['resume_latex' => $source]);
        }

        return $this->compiler->compile($source);
    }
}
