<?php

use App\Http\Controllers\SitioController;
use App\Http\Middleware\ResolverPais;
use App\Support\Pais;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [SitioController::class, 'robots']);

$categorias = implode('|', array_column(config('marca.categorias'), 'slug'));

$rutasSitio = function () use ($categorias) {
    Route::get('/{categoria}/{localidad}', [SitioController::class, 'listado'])
        ->where('categoria', $categorias)->name('listado');
    Route::get('/{categoria}/{localidad}/{aviso}', [SitioController::class, 'aviso'])
        ->where('categoria', $categorias)->name('aviso.ver');
};

// 0) Alias (www.…) → dominio principal de su marca
foreach (config('marca.marcas') as $m) {
    foreach ($m['alias'] ?? [] as $host) {
        Route::domain($host)->get('/{todo?}', [SitioController::class, 'aDominioPrincipal'])->where('todo', '.*');
    }
}

// 1) Marcas de un solo país (alojarse.cl): mismas URLs que hoy, sin carpeta de país.
foreach (config('marca.marcas') as $clave => $m) {
    if (empty($m['pais_fijo'])) {
        continue;
    }
    foreach ($m['hosts'] as $i => $host) {
        $nombre = $i === 0 ? "$clave." : "$clave.h$i.";
        Route::domain($host)->middleware(ResolverPais::class)->name($nombre)->group(function () use ($rutasSitio) {
            Route::get('/', [SitioController::class, 'inicio'])->name('inicio');
            $rutasSitio();
        });
    }
}

// 2) localtur (multipaís): /cl, /pe, /co
Route::middleware(ResolverPais::class)->group(function () use ($rutasSitio) {
    Route::get('/', [SitioController::class, 'paises'])->name('raiz');
    Route::prefix('{pais}')->where(['pais' => implode('|', Pais::codigos())])->group(function () use ($rutasSitio) {
        Route::get('/', [SitioController::class, 'inicio'])->name('inicio');
        $rutasSitio();
    });
});
