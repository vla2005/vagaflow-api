<?php

namespace App\Services;

use App\Models\AutomationProfile;
use App\Support\OpportunityTaxonomy;
use Illuminate\Support\Str;

class JobFilter
{
    private const DEVELOPMENT_TERMS = [
        'desenvolvedor', 'desenvolvedora', 'developer', 'dev ', 'dev.', 'full stack',
        'fullstack', 'backend', 'back-end', 'frontend', 'front-end', 'software',
        'programador', 'programadora', 'engenheiro de software', 'analista de sistemas',
        'web', 'php', 'laravel', 'java', 'spring', 'javascript', 'typescript', 'vue',
        'react', 'node', 'mobile', 'android', 'ios', 'devops', 'qa', 'dados',
    ];

    public function matchesLevel(array $job, string $level): bool
    {
        return $this->normalize($job['nivel'] ?? $job['nivel_vaga'] ?? '') === $this->normalize($level);
    }

    public function titleMatches(array $job): bool
    {
        return $this->hasAny($this->join($job, ['titulo_vaga', 'titulo', 'cargo', 'slug']), self::DEVELOPMENT_TERMS);
    }

    public function matchesProfile(array $job, AutomationProfile $profile): bool
    {
        if ($this->isTrue($job['vaga_encerrada'] ?? $job['is_closed'] ?? false)) {
            return false;
        }

        $level = $this->normalize($job['nivel'] ?? $job['nivel_vaga'] ?? '');
        if (! in_array($level, $profile->seniorities, true)) {
            return false;
        }

        $title = $this->join($job, ['titulo_vaga', 'titulo', 'cargo', 'slug']);
        $fullText = $title.' '.$this->join($job, [
            'descricao_vaga', 'requisitos_tecnicos', 'requisitos_desejaveis',
        ]).' '.implode(' ', $job['tecnologias'] ?? []);

        if ($this->hasAny($fullText, $profile->excluded_keywords)) {
            return false;
        }

        $titleMatch = $profile->job_titles === [] || $this->hasAny($title, $profile->job_titles);
        $technologyMatch = $profile->technologies === [] || $this->hasAny($fullText, $profile->technologies);

        if (! $titleMatch || ! $technologyMatch) {
            return false;
        }

        $mode = $this->normalize($job['forma_trabalho'] ?? $job['work_mode'] ?? '');
        if ($this->restricts($profile->work_modes, ['remoto', 'hibrido', 'presencial'])
            && ! $this->hasAny($mode, $profile->work_modes)) {
            return false;
        }

        $area = $this->normalize($job['cargo'] ?? $job['area'] ?? '');
        if (($profile->areas ?? []) !== [] && ! in_array($area, $profile->areas, true)) {
            return false;
        }

        $platform = $this->normalize($job['plataforma'] ?? $job['platform'] ?? '');
        if ($this->restricts($profile->platforms ?? [], OpportunityTaxonomy::PLATFORMS)
            && ! in_array($platform, $profile->platforms, true)) {
            return false;
        }

        $employmentType = $this->normalize($job['tipo_contratacao'] ?? $job['tipo_contrato'] ?? $job['employment_type'] ?? '');
        if ($this->restricts($profile->employment_types ?? [], OpportunityTaxonomy::EMPLOYMENT_TYPES)
            && ! in_array($employmentType, $profile->employment_types, true)) {
            return false;
        }

        if ($profile->easy_apply_only && ! $this->isTrue($job['vaga_easy_apply'] ?? $job['is_easy_apply'] ?? false)) {
            return false;
        }

        if ($this->hasAny($mode, ['remoto', 'remote'])) {
            return true;
        }

        if ($profile->locations === []) {
            return true;
        }

        return $this->hasAny((string) ($job['local'] ?? $job['location'] ?? ''), $profile->locations);
    }

    private function join(array $job, array $fields): string
    {
        return implode(' ', array_map(fn (string $field) => (string) ($job[$field] ?? ''), $fields));
    }

    private function hasAny(string $text, array $keywords): bool
    {
        $normalized = $this->normalize($text);

        foreach ($keywords as $keyword) {
            $keyword = trim($this->normalize((string) $keyword));
            if ($keyword !== '' && preg_match(
                '/(?<![\p{L}\p{N}])'.preg_quote($keyword, '/').'(?![\p{L}\p{N}])/u',
                $normalized,
            ) === 1) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::ascii($value));
    }

    private function isTrue(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function restricts(array $selected, array $available): bool
    {
        return $selected !== [] && array_diff($available, $selected) !== [];
    }
}
