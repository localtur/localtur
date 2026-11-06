<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['CL' => ['Chile', 'CLP'], 'PE' => ['Perú', 'PEN'], 'CO' => ['Colombia', 'COP']] as $codigo => [$nombre, $moneda]) {
            if (! DB::table('paises')->where('codigo', $codigo)->exists()) {
                DB::table('paises')->insert(['codigo' => $codigo, 'nombre' => $nombre, 'moneda' => $moneda, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        DB::table('paises')->whereIn('codigo', ['PE', 'CO'])->whereNotExists(fn ($q) => $q->from('regiones')->whereColumn('regiones.pais_id', 'paises.id'))->delete();
    }
};
