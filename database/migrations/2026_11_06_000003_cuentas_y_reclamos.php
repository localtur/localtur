<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contacto público: solo se muestra cuando el aviso está activado por su dueño.
        Schema::table('avisos', function (Blueprint $t) {
            $t->string('contacto_telefono', 30)->nullable()->after('descripcion');
            $t->string('contacto_whatsapp', 30)->nullable()->after('contacto_telefono');
            $t->string('contacto_correo')->nullable()->after('contacto_whatsapp');
            $t->string('contacto_web')->nullable()->after('contacto_correo');
        });

        // "¿Es tu negocio?": solicitud para tomar un aviso sin activar. La aprueba el equipo.
        Schema::create('reclamos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('aviso_id')->constrained('avisos')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('relacion', 30);                  // dueno, administrador, encargado
            $t->string('telefono', 30);
            $t->text('mensaje')->nullable();
            $t->string('estado', 20)->default('pendiente')->index(); // pendiente, aprobado, rechazado
            $t->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('revisado_en')->nullable();
            $t->string('motivo_rechazo')->nullable();
            $t->timestamps();
            $t->unique(['aviso_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamos');
        Schema::table('avisos', fn (Blueprint $t) => $t->dropColumn(['contacto_telefono', 'contacto_whatsapp', 'contacto_correo', 'contacto_web']));
    }
};
