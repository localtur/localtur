<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Importador del registro de prestadores turísticos → alojarse.cl. Hostinger corta los procesos largos,
// así que corre en tandas cortas cada 10 minutos entre 01:00 y 06:50 (hora de Chile); cada tanda sigue
// donde quedó la anterior. En Hostinger basta un cron que corra cada minuto: php artisan schedule:run
// Registro de cada tanda: storage/logs/registro.log
\Illuminate\Support\Facades\Schedule::command('registro:importar', [
    '--paginas' => config('registro.tanda.paginas'),
    '--fichas' => config('registro.tanda.fichas'),
    '--minutos' => config('registro.tanda.minutos'),
])->cron('*/10 1-6 * * *')->timezone('America/Santiago')->withoutOverlapping(15)
    ->appendOutputTo(storage_path('logs/registro.log'));
