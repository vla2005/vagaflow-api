<?php

namespace App\Services;

use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;

class PdfTextExtractor
{
    public function extract(string $path): string
    {
        try {
            $text = $this->extractWithPoppler($path)
                ?? (new Parser)->parseFile($path)->getText();
        } catch (Throwable $exception) {
            report($exception);

            throw new RuntimeException('Não foi possível ler este PDF. Verifique se ele não está protegido ou corrompido.');
        }

        $text = str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? '';
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? '';
        $text = trim($text);

        preg_match_all('/\p{L}[\p{L}\p{M}\'-]*/u', $text, $words);
        $letters = preg_replace('/[^\p{L}]/u', '', $text) ?? '';

        if (mb_strlen($text) < 120 || count($words[0]) < 20 || mb_strlen($letters) < 80) {
            throw new RuntimeException('Este PDF não possui texto suficiente para extração. Envie um currículo com texto selecionável, não uma imagem escaneada.');
        }

        return mb_substr($text, 0, 100000);
    }

    private function extractWithPoppler(string $path): ?string
    {
        $binary = '/usr/bin/pdftotext';

        if (! is_executable($binary)) {
            return null;
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'vagaflow-resume-');

        if ($temporaryPath === false || ! copy($path, $temporaryPath)) {
            throw new RuntimeException('Não foi possível preparar o PDF enviado para leitura.');
        }

        try {
            $process = @proc_open(
                [$binary, '-layout', '-enc', 'UTF-8', $temporaryPath, '-'],
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                $pipes,
            );

            if (! is_resource($process)) {
                throw new RuntimeException('Não foi possível iniciar a leitura do PDF enviado.');
            }

            fclose($pipes[0]);
            $text = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $error = trim((string) stream_get_contents($pipes[2]));
            fclose($pipes[2]);

            $exitCode = proc_close($process);

            if ($exitCode !== 0 || ! is_string($text) || trim($text) === '') {
                report(new RuntimeException("pdftotext falhou com código {$exitCode}: {$error}"));

                throw new RuntimeException('Não foi possível extrair o texto completo deste PDF.');
            }

            return $text;
        } finally {
            @unlink($temporaryPath);
        }
    }
}
