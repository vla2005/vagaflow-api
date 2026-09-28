<?php

namespace App\Services;

class LatexEscaper
{
    public function escape(?string $value): string
    {
        return strtr(trim((string) $value), [
            '\\' => '\\textbackslash{}',
            '{' => '\\{',
            '}' => '\\}',
            '#' => '\\#',
            '$' => '\\$',
            '%' => '\\%',
            '&' => '\\&',
            '_' => '\\_',
            '^' => '\\textasciicircum{}',
            '~' => '\\textasciitilde{}',
        ]);
    }
}
