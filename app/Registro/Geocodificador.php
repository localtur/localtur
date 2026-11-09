<?php

namespace App\Registro;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Coordenadas con OpenStreetMap (Nominatim): máximo 1 consulta por segundo y respuestas guardadas,
 * como pide su política de uso. Prueba la dirección exacta y, si no resulta, la localidad y la comuna.
 */
class Geocodificador
{
    private float $ultima = 0;

    public function __construct(private string $url, private string $agente) {}

    /** @return array{0: float, 1: float}|null */
    public function buscar(string $direccion, string $localidad, string $comuna, string $region): ?array
    {
        $region = preg_replace('/^Regi[oó]n (de |del )?/iu', '', $region);
        $intentos = array_unique(array_filter([
            $direccion && $comuna ? "$direccion, $comuna, Chile" : '',
            $localidad && $localidad !== $comuna ? "$localidad, $comuna, $region, Chile" : '',
            $comuna ? "$comuna, $region, Chile" : '',
        ]));
        foreach ($intentos as $q) {
            $r = $this->consultar(mb_substr($q, 0, 300));
            if ($r) {
                return $r;
            }
        }

        return null;
    }

    private function consultar(string $q): ?array
    {
        $guardada = DB::table('registro_geocodigos')->where('consulta', $q)->first();
        if ($guardada) {
            return $guardada->lat !== null ? [(float) $guardada->lat, (float) $guardada->lng] : null;
        }
        $espera = 1.1 - (microtime(true) - $this->ultima);
        if ($espera > 0) {
            usleep((int) ($espera * 1e6));
        }
        $this->ultima = microtime(true);
        $res = null;
        try {
            $r = Http::withHeaders(['User-Agent' => $this->agente, 'Accept-Language' => 'es'])->timeout(20)
                ->get($this->url, ['q' => $q, 'format' => 'json', 'limit' => 1, 'countrycodes' => 'cl']);
            if ($r->ok() && isset($r->json()[0]['lat'])) {
                $res = [(float) $r->json()[0]['lat'], (float) $r->json()[0]['lon']];
            } elseif (! $r->ok()) {
                return null; // error temporal: no se guarda, se reintenta otro día
            }
        } catch (\Throwable) {
            return null;
        }
        DB::table('registro_geocodigos')->insert(['consulta' => $q, 'lat' => $res[0] ?? null, 'lng' => $res[1] ?? null, 'created_at' => now(), 'updated_at' => now()]);

        return $res;
    }
}
