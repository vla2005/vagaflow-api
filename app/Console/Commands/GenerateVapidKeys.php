<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature = 'vagaflow:vapid-keys';

    protected $description = 'Gera um par de chaves VAPID para as notificações Web Push';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->components->warn('Guarde a chave privada somente no ambiente da API.');
        $this->line('WEBPUSH_VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('WEBPUSH_VAPID_PRIVATE_KEY='.$keys['privateKey']);

        return self::SUCCESS;
    }
}
