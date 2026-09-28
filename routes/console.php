<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('vagaflow:create-user', function () {
    $email = $this->ask('E-mail');
    $name = $this->ask('Nome');
    $password = $this->secret('Senha');

    $validator = Validator::make(compact('email', 'name', 'password'), [
        'email' => ['required', 'email', 'unique:users,email'],
        'name' => ['required', 'string', 'max:255'],
        'password' => ['required', 'string', 'min:12'],
    ]);

    if ($validator->fails()) {
        $this->error($validator->errors()->first());

        return 1;
    }

    User::create(compact('email', 'name', 'password'));
    $this->info('Usuário criado. Ele já pode configurar a automação pelo aplicativo.');

    return 0;
})->purpose('Cria o usuário inicial do VagaFlow');

Schedule::command('vagaflow:search')
    ->everyThirtyMinutes()
    ->withoutOverlapping(25)
    ->onOneServer();

Schedule::command('vagaflow:dispatch-resume-parsing')
    ->everyFiveMinutes()
    ->withoutOverlapping(4)
    ->onOneServer();
