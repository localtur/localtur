<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('rol')->default('viajero')->after('email');   // viajero, anfitrion, administrador, moderador, admin
            $t->string('telefono')->nullable()->after('rol');
        });

        // Aviso público: todo lo que hay aquí puede verse en el sitio y leerlo Google.
        Schema::create('avisos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('localidad_id')->constrained('localidades');
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();   // dueño, cuando activa
            $t->string('categoria', 20)->index();      // alojar, comer, hacer, comercio, servicios
            $t->string('subtipo')->nullable();
            $t->string('titulo', 120);
            $t->string('slug');
            $t->string('descripcion_corta', 160)->nullable();
            $t->text('descripcion')->nullable();
            $t->string('direccion')->nullable();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->boolean('ubicacion_aproximada')->default(true);
            // sin_activar, activo_gratis, completo, anual, basico, pausado
            $t->string('estado', 20)->default('sin_activar')->index();
            $t->string('tramo')->nullable();            // particular, pequeno, mediano, grande...
            $t->date('plan_vence')->nullable();
            $t->string('origen', 20)->default('propio'); // registro_publico, propio, base44
            $t->string('imagen_referencial')->nullable();
            $t->json('fotos')->nullable();              // hasta 10 rutas
            $t->boolean('indexable')->default(false);
            $t->timestamps();
            $t->unique(['localidad_id', 'categoria', 'slug']);
        });

        // Datos privados: nunca se muestran en el sitio público. Solo admin.
        Schema::create('avisos_privados', function (Blueprint $t) {
            $t->id();
            $t->foreignId('aviso_id')->unique()->constrained('avisos')->cascadeOnDelete();
            $t->string('id_semilla')->nullable()->unique();
            $t->string('fuente')->nullable();            // archivo o proceso de origen
            $t->string('nombre_original')->nullable();
            $t->string('clase_original')->nullable();
            $t->string('razon_social')->nullable();
            $t->string('rut')->nullable();
            $t->string('telefono_original')->nullable();
            $t->string('telefono')->nullable();
            $t->string('estado_telefono')->nullable();
            $t->string('correo')->nullable();
            $t->string('web')->nullable();
            $t->boolean('no_contactar')->default(false); // lista de exclusión
            $t->text('notas')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos_privados');
        Schema::dropIfExists('avisos');
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['rol', 'telefono']);
        });
    }
};
