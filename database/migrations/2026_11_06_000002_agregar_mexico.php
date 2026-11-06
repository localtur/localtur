<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('paises')->where('codigo', 'MX')->exists()) {
            DB::table('paises')->insert(['codigo' => 'MX', 'nombre' => 'México', 'moneda' => 'MXN', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('paises')->where('codigo', 'MX')->whereNotExists(fn ($q) => $q->from('regiones')->whereColumn('regiones.pais_id', 'paises.id'))->delete();
    }
};
