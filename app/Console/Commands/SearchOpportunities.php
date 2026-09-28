<?php

namespace App\Console\Commands;

use App\Services\OpportunitySearch;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('vagaflow:search {level?}')]
#[Description('Coleta vagas e agenda a avaliação dos perfis ativos')]
class SearchOpportunities extends Command
{
    public function handle(OpportunitySearch $search): int
    {
        try {
            $level = $this->argument('level');
            $result = $level ? $search->run($level) : $search->runAll();
            $this->line(json_encode($result, JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
