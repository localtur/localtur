<?php

use App\Http\Controllers\SitioController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [SitioController::class, 'robots']);
Route::get('/', [SitioController::class, 'inicio'])->name('inicio');

// Misma forma de URL que alojarse.cl: /alojamiento/puerto-varas/cabana-mirador-del-lago
$categorias = implode('|', array_column(config('marca.categorias'), 'slug'));

Route::get('/{categoria}/{localidad}', [SitioController::class, 'listado'])
    ->where('categoria', $categorias)
    ->name('listado');

Route::get('/{categoria}/{localidad}/{aviso}', [SitioController::class, 'aviso'])
    ->where('categoria', $categorias)
    ->name('aviso.ver');
