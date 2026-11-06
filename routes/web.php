<?php

use App\Http\Controllers\AvisoDuenoController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\SitioController;
use App\Http\Middleware\ResolverPais;
use App\Support\Pais;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [SitioController::class, 'robots']);

// Cuentas: mismas rutas en todas las marcas (alojarse.cl/cuenta, localtur.com/cuenta)
Route::middleware(ResolverPais::class)->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/ingresar', [CuentaController::class, 'formIngreso'])->name('login');
        Route::post('/ingresar', [CuentaController::class, 'ingresar'])->name('ingresar');
        Route::get('/registro', [CuentaController::class, 'formRegistro'])->name('registro');
        Route::post('/registro', [CuentaController::class, 'registrar'])->name('registrar');
    });
    Route::middleware('auth')->prefix('cuenta')->name('cuenta.')->group(function () {
        Route::get('/', [CuentaController::class, 'inicio'])->name('inicio');
        Route::post('/salir', [CuentaController::class, 'salir'])->name('salir');
        Route::get('/reclamar/{aviso}', [AvisoDuenoController::class, 'formReclamar'])->name('reclamar');
        Route::post('/reclamar/{aviso}', [AvisoDuenoController::class, 'reclamar'])->name('reclamar.enviar');
        Route::get('/avisos/nuevo', [AvisoDuenoController::class, 'formNuevo'])->name('aviso.nuevo');
        Route::post('/avisos', [AvisoDuenoController::class, 'crear'])->name('aviso.crear');
        Route::get('/avisos/{aviso}/editar', [AvisoDuenoController::class, 'formEditar'])->name('aviso.editar');
        Route::put('/avisos/{aviso}', [AvisoDuenoController::class, 'actualizar'])->name('aviso.actualizar');
    });
});

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
