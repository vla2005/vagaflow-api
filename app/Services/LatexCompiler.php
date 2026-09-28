<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

class LatexCompiler
{
    public function compile(string $source): string
    {
        $directory = storage_path('app/private/latex/'.Str::uuid());
        $texPath = $directory.DIRECTORY_SEPARATOR.'resume.tex';
        $pdfPath = $directory.DIRECTORY_SEPARATOR.'resume.pdf';

        File::ensureDirectoryExists($directory);
        File::put($texPath, $source);

        try {
            $result = Process::path($directory)
                ->timeout(90)
                ->run([
                    config('vagaflow.latex_binary'),
                    '-interaction=nonstopmode',
                    '-halt-on-error',
                    '-no-shell-escape',
                    'resume.tex',
                ]);

            if ($result->failed() || ! File::exists($pdfPath)) {
                $details = trim($result->errorOutput().' '.$result->output());
                throw new RuntimeException('Falha ao compilar o currículo em LaTeX: '.mb_substr($details, -1500));
            }

            return File::get($pdfPath);
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
