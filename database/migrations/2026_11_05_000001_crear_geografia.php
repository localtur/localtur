<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paises', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 2)->unique();
            $t->string('nombre');
            $t->string('moneda', 3)->default('CLP');
            $t->timestamps();
        });

        Schema::create('regiones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pais_id')->constrained('paises')->cascadeOnDelete();
            $t->string('nombre');
            $t->string('slug');
            $t->unsignedSmallInteger('orden')->default(0);
            $t->timestamps();
            $t->unique(['pais_id', 'slug']);
        });

        Schema::create('comunas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('region_id')->constrained('regiones')->cascadeOnDelete();
            $t->string('nombre');
            $t->string('slug');
            $t->unsignedInteger('poblacion')->nullable();   // para el descuento de comuna pequeña
            $t->timestamps();
            $t->unique(['region_id', 'slug']);
        });

        Schema::create('localidades', function (Blueprint $t) {
            $t->id();
            $t->foreignId('comuna_id')->constrained('comunas')->cascadeOnDelete();
            $t->string('nombre');
            $t->string('slug')->index();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->string('destino')->nullable();              // zona turística ("Lagos Llanquihue y Todos Los Santos")
            $t->timestamps();
            $t->unique(['comuna_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localidades');
        Schema::dropIfExists('comunas');
        Schema::dropIfExists('regiones');
        Schema::dropIfExists('paises');
    }
};
