<?php

namespace App\Console\Commands;

use App\Services\StoredOpportunityDispatcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('vagaflow:reevaluate {email} {--limit=200}')]
#[Description('Envia vagas armazenadas e ainda não avaliadas para a queue')]
class ReevaluateStoredOpportunities extends Command
{
    public function handle(StoredOpportunityDispatcher $dispatcher): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 1000],
        ]);

        if ($limit === false) {
            $this->error('O limite deve ser um número entre 1 e 1000.');

            return self::FAILURE;
        }

        $stats = $dispatcher->dispatch((string) $this->argument('email'), $limit);

        if ($stats['profiles'] === 0) {
            $this->error('Nenhum perfil ativo e pronto foi encontrado para esse e-mail.');

            return self::FAILURE;
        }

        $this->line(json_encode($stats, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
