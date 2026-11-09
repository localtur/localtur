<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Estado del importador del registro de prestadores (alojarse.cl). Solo control interno: los datos de
        // contacto no se guardan aquí, se envían directo a alojarse, que los guarda en privado.
        Schema::create('registro_fichas', function (Blueprint $t) {
            $t->id();
            $t->string('registro_id', 20)->unique();
            $t->string('tipo', 4)->index();
            $t->string('url', 300);
            $t->string('estado', 20)->default('pendiente')->index(); // pendiente, creado, actualizado, protegido, excluido, no_vigente, no_encontrada, error
            $t->string('huella', 40)->nullable();                    // sha1 de los datos enviados (para no reenviar lo mismo)
            $t->unsignedTinyInteger('fotos')->default(0);
            $t->string('entidad', 30)->nullable();
            $t->string('aviso_id', 40)->nullable();
            $t->unsignedSmallInteger('intentos')->default(0);
            $t->string('error', 250)->nullable();
            $t->timestamp('revisado_at')->nullable()->index();
            $t->timestamps();
        });

        // Fotos ya optimizadas y subidas: por huella del archivo original, para no subirlas dos veces
        // y para detectar imágenes genéricas que se repiten en muchas fichas.
        Schema::create('registro_fotos', function (Blueprint $t) {
            $t->id();
            $t->string('sha1', 40)->unique();
            $t->string('ahash', 16)->nullable();
            $t->string('url', 600)->nullable();
            $t->string('primera_ficha', 20)->nullable();
            $t->unsignedInteger('usos')->default(1);
            $t->boolean('descartada')->default(false);
            $t->timestamps();
        });

        Schema::create('registro_geocodigos', function (Blueprint $t) {
            $t->id();
            $t->string('consulta', 300)->unique();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->timestamps();
        });

        Schema::create('registro_listados', function (Blueprint $t) {
            $t->id();
            $t->string('tipo', 4)->unique();
            $t->unsignedInteger('siguiente_pagina')->default(1);
            $t->unsignedInteger('total')->nullable();
            $t->timestamp('completado_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_listados');
        Schema::dropIfExists('registro_geocodigos');
        Schema::dropIfExists('registro_fotos');
        Schema::dropIfExists('registro_fichas');
    }
};
