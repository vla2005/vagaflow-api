<?php

namespace App\Services;

use App\Jobs\EvaluateOpportunityForUser;
use App\Models\AutomationProfile;
use App\Models\Job;
use App\Models\SourceJob;
use App\Support\OpportunityTaxonomy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class OpportunitySearch
{
    public const LEVELS = OpportunityTaxonomy::LEVELS;

    public function __construct(
        private MeuPadrinhoClient $source,
        private JobFilter $filter,
    ) {}

    public function run(string $level): array
    {
        if (! in_array($level, self::LEVELS, true)) {
            throw new RuntimeException('Nível inválido.');
        }

        $lock = Cache::lock("vagaflow:search:{$level}", 600);
        if (! $lock->get()) {
            return ['level' => $level, 'locked' => true];
        }

        try {
            return $this->discover($level);
        } finally {
            $lock->release();
        }
    }

    /** @return array<int, array<string, int|string|bool>> */
    public function runAll(): array
    {
        return collect(self::LEVELS)
            ->map(fn (string $level): array => $this->run($level))
            ->all();
    }

    private function discover(string $level): array
    {
        $stats = [
            'level' => $level,
            'pages' => 0,
            'listed' => 0,
            'collected' => 0,
            'reused' => 0,
            'queued' => 0,
        ];
        $profiles = AutomationProfile::query()->where('is_active', true)->with('user')->get()
            ->filter(fn (AutomationProfile $profile) => $profile->isReady() && in_array($level, $profile->seniorities, true));
        $page = 0;
        $processed = 0;
        $limit = max(1, config('vagaflow.max_source_jobs_per_level'));

        do {
            $response = $this->source->vacancies($level, $page);
            $listed = is_array($response['vagas'] ?? null) ? $response['vagas'] : [];
            $stats['pages']++;
            $newOnPage = 0;

            foreach ($listed as $summary) {
                if ($processed >= $limit) {
                    break;
                }

                $stats['listed']++;
                $processed++;

                if (! is_array($summary)) {
                    continue;
                }

                $id = (string) ($summary['nano_id'] ?? '');
                if ($id === '' || ! preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
                    continue;
                }

                $sourceJob = SourceJob::query()
                    ->where('source', 'meu_padrinho')
                    ->where('source_key', $id)
                    ->first();

                if ($sourceJob) {
                    $job = array_replace($sourceJob->payload, $summary);
                    $sourceJob->update($this->sourceAttributes($job, $level) + [
                        'payload' => $job,
                        'last_seen_at' => now(),
                    ]);
                    $stats['reused']++;
                } else {
                    $sourceJob = $this->collect($id, $level, $summary);
                    if (! $sourceJob) {
                        continue;
                    }

                    $stats['collected']++;
                    $newOnPage++;
                }

                $stats['queued'] += $this->dispatchMissingEvaluations($sourceJob, $profiles);
            }

            $hasNextPage = (bool) ($response['tem_proxima_pagina'] ?? false);
            if ($listed === [] || $processed >= $limit || ! $hasNextPage || $newOnPage === 0) {
                break;
            }

            $page++;
        } while (true);

        Log::info('VagaFlow discovery completed', $stats);

        return $stats;
    }

    private function collect(string $id, string $level, array $summary): ?SourceJob
    {
        try {
            $details = $this->source->details($id);
            $job = array_replace($summary, $details);
            $isAiVerified = $this->booleanValue($job['vaga_verificada_por_ia'] ?? null);

            if ($isAiVerified !== false) {
                $technologyResponse = $this->source->technologies($id);
                $technologies = $technologyResponse['tecnologias'] ?? [];
                $job['tecnologias'] = is_array($technologies) ? $technologies : [];
            } else {
                $job['tecnologias'] = [];
            }
        } catch (Throwable $exception) {
            Log::warning('VagaFlow could not collect source job details', [
                'level' => $level,
                'source_key' => $id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        return SourceJob::query()->create(
            ['source' => 'meu_padrinho', 'source_key' => $id]
            + $this->sourceAttributes($job, $level)
            + ['payload' => $job, 'last_seen_at' => now()],
        );
    }

    private function dispatchMissingEvaluations(SourceJob $sourceJob, $profiles): int
    {
        $evaluatedUserIds = Job::query()
            ->where('source_job_id', $sourceJob->id)
            ->pluck('user_id')
            ->all();
        $queued = 0;

        foreach ($profiles as $profile) {
            if (in_array($profile->user_id, $evaluatedUserIds, true)
                || ! $this->filter->matchesProfile($sourceJob->payload, $profile)) {
                continue;
            }

            EvaluateOpportunityForUser::dispatch($profile->id, $sourceJob->id);
            $queued++;
        }

        return $queued;
    }

    /** @return array<string, mixed> */
    private function sourceAttributes(array $job, string $level): array
    {
        return array_filter([
            'level' => $this->stringValue($job, ['nivel', 'nivel_vaga']) ?? $level,
            'area' => $this->stringValue($job, ['cargo', 'area']),
            'title' => $this->stringValue($job, ['titulo_vaga', 'titulo']) ?? 'Vaga sem título',
            'company' => $this->stringValue($job, ['nome_empresa', 'empresa_nome']),
            'location' => $this->stringValue($job, ['local', 'localizacao']),
            'work_mode' => $this->stringValue($job, ['forma_trabalho', 'work_mode']),
            'platform' => $this->stringValue($job, ['plataforma']),
            'employment_type' => $this->stringValue($job, ['tipo_contratacao', 'tipo_contrato']),
            'is_easy_apply' => $this->booleanValue($job['vaga_easy_apply'] ?? null),
            'is_ai_verified' => $this->booleanValue($job['vaga_verificada_por_ia'] ?? null),
            'is_closed' => $this->booleanValue($job['vaga_encerrada'] ?? null),
            'url' => $this->stringValue($job, ['link_vaga', 'url']),
        ], fn (mixed $value): bool => $value !== null);
    }

    private function stringValue(array $job, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (filled($job[$key] ?? null)) {
                return trim((string) $job[$key]);
            }
        }

        return null;
    }

    private function booleanValue(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
