<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MeuPadrinhoClient
{
    public function vacancies(string $level, int $page = 0): array
    {
        return $this->get('vagas', ['niveis' => $level, 'page' => $page]);
    }

    public function details(string $id): array
    {
        return $this->get("vagas/{$id}");
    }

    public function technologies(string $id): array
    {
        return $this->get("vagas/{$id}/tecnologias");
    }

    private function get(string $path, array $query = []): array
    {
        return Http::baseUrl(rtrim(config('vagaflow.source_url'), '/').'/')
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 500)
            ->get($path, $query)
            ->throw()
            ->json();
    }
}
