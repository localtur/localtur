<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Importador del registro de prestadores turísticos → alojarse.cl (todas las noches, avanza por partes).
// En Hostinger basta un cron que corra cada minuto: php artisan schedule:run
\Illuminate\Support\Facades\Schedule::command('registro:importar')
    ->dailyAt('01:40')->timezone('America/Santiago')->withoutOverlapping(360);
