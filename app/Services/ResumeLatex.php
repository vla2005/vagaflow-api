<?php

namespace App\Services;

class ResumeLatex
{
    public function __construct(private LatexEscaper $escaper) {}

    /** @param array<string, mixed> $resume */
    public function render(array $resume): string
    {
        return view('latex.resume', [
            'resume' => $this->escapeRecursively($resume),
        ])->render();
    }

    private function escapeRecursively(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->escapeRecursively($item), $value);
        }

        return is_string($value) ? $this->escaper->escape($value) : $value;
    }
}
