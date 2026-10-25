<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Chaque entreprise est traitée à partir de 7 h dans SON fuseau horaire ; la commande est idempotente.
Schedule::command('app:send-birthday-notifications')->hourly();

Schedule::command('recruitment:purge-expired')->dailyAt('02:00');
Schedule::command('leaves:accrue')->dailyAt('01:00');