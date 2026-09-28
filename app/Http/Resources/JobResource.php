<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = is_array($this->payload) ? $this->payload : [];
        $analysis = is_array($payload['analise_ia'] ?? null) ? $payload['analise_ia'] : [];
        $requirements = $this->normalizeList($payload['requisitos_tecnicos'] ?? []);
        $technologies = $this->normalizeList($payload['tecnologias'] ?? []);

        if ($technologies === []) {
            $technologies = array_slice($requirements, 0, 8);
        }

        return [
            'id' => $this->id,
            'level' => $this->level,
            'title' => $this->title,
            'company' => $this->company,
            'location' => $this->location,
            'work_mode' => $this->work_mode,
            'url' => $this->url,
            'score' => $this->score,
            'status' => $this->status,
            'description' => $payload['descricao_vaga'] ?? null,
            'requirements' => $requirements,
            'desired_requirements' => $this->normalizeList($payload['requisitos_desejaveis'] ?? []),
            'benefits' => $this->normalizeList($payload['beneficios'] ?? []),
            'technologies' => $technologies,
            'contract_type' => $payload['tipo_contrato'] ?? $payload['tipo_contratacao'] ?? null,
            'platform' => $payload['plataforma'] ?? null,
            'easy_apply' => (bool) ($payload['vaga_easy_apply'] ?? false),
            'ai_verified' => (bool) ($payload['vaga_verificada_por_ia'] ?? false),
            'analysis' => [
                'reason' => $analysis['motivo'] ?? null,
                'summary' => $analysis['resumo'] ?? null,
                'strengths' => $this->normalizeList($analysis['pontos_fortes'] ?? []),
                'attention_points' => $this->normalizeList($analysis['pontos_atencao'] ?? []),
                'preparation' => $analysis['sugestao_preparacao'] ?? null,
            ],
            'payload' => $this->payload,
            'discovered_at' => $this->discovered_at,
            'sent_at' => $this->sent_at,
            'viewed_at' => $this->viewed_at,
            'has_personalized_resume' => filled($this->resume_latex),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @return list<string>
     */
    private function normalizeList(mixed $value): array
    {
        $items = is_array($value)
            ? $value
            : (preg_split('/\s*;\s*|\r?\n|\s*•\s*/u', (string) $value) ?: []);

        return array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item, " \t\n\r\0\x0B-"),
            $items,
        )));
    }
}
