<?php

namespace App\Console\Commands;

use App\Registro\ClienteAlojarse;
use App\Registro\Geocodificador;
use App\Registro\OptimizadorFotos;
use App\Registro\Parser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Importador nocturno del registro público de prestadores turísticos hacia alojarse.cl.
 *
 * 1. Recorre el listado de cada tipo de servicio (todos menos transporte aéreo y ferroviario) y anota las fichas.
 * 2. Lee cada ficha: datos, coordenadas (las del mapa o, si no hay, OpenStreetMap) y fotos.
 * 3. Fotos: hasta 10 de las que el propio negocio publicó al inscribirse; descarta "sin foto", imágenes genéricas
 *    que se repiten en muchas fichas, íconos y duplicados (aunque estén en otro tamaño); las deja en WebP liviano
 *    y las sube a alojarse.
 * 4. Envía los avisos a alojarse en lotes de 25. Alojarse no toca los avisos activados por su dueño y guarda el
 *    teléfono y el correo del registro en privado.
 *
 * Avanza por partes cada noche (límite de fichas, páginas y minutos) y retoma donde quedó.
 */
class ImportarRegistro extends Command
{
    protected $signature = 'registro:importar
        {--tipo=* : Solo estos tipo_servicio del registro (ej. --tipo=1 --tipo=2)}
        {--fichas= : Máximo de fichas a leer en esta pasada}
        {--paginas= : Máximo de páginas del listado en esta pasada}
        {--minutos= : Máximo de minutos de esta pasada}
        {--solo-listado : Solo recorrer el listado, sin leer fichas}
        {--ficha= : Leer solo esta ficha (número de inscripción) y mostrar lo que se enviaría}
        {--simular : No sube fotos ni envía nada a alojarse}
        {--probar : Probar la conexión con alojarse y terminar}';

    protected $description = 'Importa el registro de prestadores turísticos a alojarse.cl (con fotos optimizadas)';

    private Parser $parser;

    private OptimizadorFotos $optimizador;

    private ?Geocodificador $geo = null;

    private ClienteAlojarse $alojarse;

    private float $limite;

    private array $cuenta = ['paginas' => 0, 'nuevas' => 0, 'leidas' => 0, 'enviadas' => 0, 'sin_cambios' => 0, 'fotos_subidas' => 0, 'fotos_descartadas' => 0, 'errores' => 0];

    public function handle(): int
    {
        $cfg = config('registro');
        $this->parser = new Parser($cfg['base']);
        $this->optimizador = new OptimizadorFotos($cfg['fotos']);
        $this->alojarse = new ClienteAlojarse($cfg['alojarse']['url'], $cfg['alojarse']['secreto']);
        if ($cfg['geocodificar']) {
            $this->geo = new Geocodificador($cfg['nominatim'], $cfg['agente']);
        }
        $this->limite = microtime(true) + 60 * ((int) $this->option('minutos') ?: $cfg['minutos_max']);

        if ($this->option('probar')) {
            $r = $this->alojarse->estado();
            $this->info('Conexión con alojarse: ' . json_encode($r, JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        if ($id = $this->option('ficha')) {
            return $this->unaFicha((string) $id);
        }

        $tipos = array_keys($cfg['tipos']);
        if ($this->option('tipo')) {
            $tipos = array_values(array_intersect($tipos, $this->option('tipo')));
        }
        $tipos = array_values(array_diff($tipos, $cfg['excluidos']));

        $this->listar($tipos, (int) ($this->option('paginas') ?: $cfg['paginas_por_noche']));
        if (! $this->option('solo-listado')) {
            $this->leerFichas($tipos, (int) ($this->option('fichas') ?: $cfg['fichas_por_noche']));
        }

        $this->line(now('America/Santiago')->format('Y-m-d H:i') . ' · fin de la pasada');
        $this->table(array_keys($this->cuenta), [array_values($this->cuenta)]);
        $this->resumenEstados();

        return self::SUCCESS;
    }

    /** Paso 1: anota las fichas del listado. Cada tipo se recorre completo cada `relistar_dias`. */
    private function listar(array $tipos, int $maxPaginas): void
    {
        $cfg = config('registro');
        foreach ($tipos as $tipo) {
            $l = DB::table('registro_listados')->where('tipo', $tipo)->first();
            if (! $l) {
                DB::table('registro_listados')->insert(['tipo' => $tipo, 'siguiente_pagina' => 1, 'created_at' => now(), 'updated_at' => now()]);
                $l = DB::table('registro_listados')->where('tipo', $tipo)->first();
            }
            if ($l->completado_at && $l->siguiente_pagina == 1 && now()->subDays($cfg['relistar_dias'])->lt($l->completado_at)) {
                continue;
            }
            $pagina = (int) $l->siguiente_pagina;
            while ($this->cuenta['paginas'] < $maxPaginas && $this->hayTiempo()) {
                $url = $cfg['base'] . '/resultado.php?' . http_build_query(['page' => $pagina, 'nombre' => '', 'tipo_servicio' => $tipo, 'comuna_nombre' => '']);
                [$estado, $html] = $this->leer($url);
                $this->cuenta['paginas']++;
                if ($estado !== 200) {
                    $this->warn("Listado tipo $tipo página $pagina: respuesta $estado; se retoma otro día.");
                    break;
                }
                $fichas = $this->parser->listado($html);
                $total = max((int) $this->parser->total($html), (int) $l->total) ?: null;
                if (! $fichas) {
                    DB::table('registro_listados')->where('tipo', $tipo)->update(['siguiente_pagina' => 1, 'total' => $total, 'completado_at' => now(), 'updated_at' => now()]);
                    $this->line("Tipo $tipo: listado completo ($total servicios).");
                    break;
                }
                foreach ($fichas as $f) {
                    $this->cuenta['nuevas'] += DB::table('registro_fichas')->insertOrIgnore([
                        'registro_id' => $f['id'], 'tipo' => $tipo, 'url' => $f['url'], 'estado' => 'pendiente', 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $pagina++;
                DB::table('registro_listados')->where('tipo', $tipo)->update(['siguiente_pagina' => $pagina, 'total' => $total, 'updated_at' => now()]);
                if ($total && ($pagina - 1) * 20 >= $total + 20) {
                    DB::table('registro_listados')->where('tipo', $tipo)->update(['siguiente_pagina' => 1, 'completado_at' => now()]);
                    break;
                }
            }
        }
    }

    /** Paso 2: lee fichas nuevas, con error o que hace tiempo no se revisan, y las envía en lotes. */
    private function leerFichas(array $tipos, int $max): void
    {
        $cfg = config('registro');
        $fichas = DB::table('registro_fichas')
            ->whereIn('tipo', $tipos)
            ->where(fn ($q) => $q->where('estado', 'pendiente')
                ->orWhere(fn ($q) => $q->where('estado', 'error')->where('intentos', '<', 5))
                ->orWhere('revisado_at', '<', now()->subDays($cfg['revisar_dias'])))
            ->orderByRaw('revisado_at is not null, revisado_at')
            ->limit($max)->get();

        $lote = [];
        $fallosSeguidos = 0;
        foreach ($fichas as $ficha) {
            if (! $this->hayTiempo()) {
                $this->warn('Se acabó el tiempo de esta noche; mañana sigue.');
                break;
            }
            try {
                $item = $this->armarItem($ficha);
                $this->cuenta['leidas']++;
                if ($item === null) {
                    continue;
                }
                $huella = sha1(json_encode($item));
                if ($huella === $ficha->huella && in_array($ficha->estado, ['creado', 'actualizado', 'protegido'], true)) {
                    DB::table('registro_fichas')->where('id', $ficha->id)->update(['revisado_at' => now(), 'updated_at' => now()]);
                    $this->cuenta['sin_cambios']++;

                    continue;
                }
                $lote[$item['id']] = ['item' => $item, 'huella' => $huella];
            } catch (Throwable $e) {
                $this->marcarError($ficha->registro_id, $e->getMessage());
            }
            if (count($lote) >= $cfg['alojarse']['lote']) {
                $fallosSeguidos = $this->enviar($lote) ? 0 : $fallosSeguidos + 1;
                $lote = [];
                if ($fallosSeguidos >= 3) {
                    $this->error('alojarse no responde; se detiene y sigue mañana.');

                    return;
                }
            }
        }
        if ($lote) {
            $this->enviar($lote);
        }
    }

    /** Lee una ficha y arma lo que se envía. null si la ficha ya no existe o no sirve. */
    private function armarItem(object $ficha): ?array
    {
        [$estado, $html] = $this->leer($ficha->url);
        if ($estado === 404 || $estado === 410) {
            DB::table('registro_fichas')->where('id', $ficha->id)->update(['estado' => 'no_encontrada', 'revisado_at' => now(), 'updated_at' => now()]);

            return null;
        }
        if ($estado !== 200) {
            throw new \RuntimeException("La ficha respondió $estado");
        }
        $d = $this->parser->detalle($html, $ficha->url);
        if (! $d['nombre']) {
            throw new \RuntimeException('La ficha no trae nombre (¿cambió la página?)');
        }
        if ($d['lat'] === null && $this->geo) {
            [$d['lat'], $d['lng']] = $this->geo->buscar($d['direccion'], $d['localidad'], $d['comuna'], $d['region']) ?? [null, null];
        }
        [$fotos, $completas] = $this->fotos($ficha->registro_id, $d['nombre'], $d['fotos']);

        return [
            'id' => $ficha->registro_id, 'tipo' => $ficha->tipo, 'url' => $ficha->url,
            'nombre' => $d['nombre'], 'clasificacion' => $d['clasificacion'], 'region' => $d['region'],
            'comuna' => $d['comuna'], 'localidad' => $d['localidad'], 'direccion' => $d['direccion'],
            'telefono' => $d['telefono'], 'email' => $d['email'], // alojarse los guarda en privado; nunca se publican
            'lat' => $d['lat'], 'lng' => $d['lng'], 'vigente' => $d['vigente'],
            'fotos' => $fotos, 'fotos_completas' => $completas,
        ];
    }

    /**
     * Descarga, filtra, optimiza y sube las fotos. Devuelve [urls, completas]; completas = false si alguna
     * descarga falló por la red (así alojarse no borra fotos buenas por un error pasajero).
     */
    private function fotos(string $fichaId, string $nombre, array $fuentes): array
    {
        $cfg = config('registro.fotos');
        $urls = [];
        $huellas = [];
        $completas = true;
        foreach (array_slice($fuentes, 0, 20) as $src) {
            if (count($urls) >= $cfg['max']) {
                break;
            }
            [$estado, $bytes] = $this->leer($src, true);
            if ($estado !== 200 || $bytes === '') {
                $completas = $completas && $estado >= 400 && $estado < 500;

                continue;
            }
            $sha = sha1($bytes);
            $fila = DB::table('registro_fotos')->where('sha1', $sha)->first();
            if ($fila) {
                if ($fila->primera_ficha !== $fichaId && ! $fila->descartada) {
                    $usos = $fila->usos + 1;
                    $generica = $usos >= $cfg['generica_usos'];
                    DB::table('registro_fotos')->where('id', $fila->id)->update(['usos' => $usos, 'descartada' => $generica, 'updated_at' => now()]);
                    if ($generica) {
                        $this->cuenta['fotos_descartadas']++;

                        continue;
                    }
                }
                if ($fila->descartada || ! $fila->url || $this->parecida($fila->ahash, $huellas)) {
                    continue;
                }
                $urls[] = $fila->url;
                $huellas[] = $fila->ahash;

                continue;
            }
            $opt = $this->optimizador->procesar($bytes);
            if (! $opt || $this->parecida($opt['ahash'], $huellas)) {
                DB::table('registro_fotos')->insert(['sha1' => $sha, 'ahash' => $opt['ahash'] ?? null, 'url' => null, 'primera_ficha' => $fichaId, 'descartada' => ! $opt, 'created_at' => now(), 'updated_at' => now()]);
                $this->cuenta['fotos_descartadas']++;

                continue;
            }
            $url = $this->option('simular') ? 'https://simulada.invalid/' . $sha . '.webp' : $this->alojarse->subirFoto($opt['webp'], Str::slug($nombre) . '-' . (count($urls) + 1));
            if (! $this->option('simular')) {
                DB::table('registro_fotos')->insert(['sha1' => $sha, 'ahash' => $opt['ahash'], 'url' => $url, 'primera_ficha' => $fichaId, 'created_at' => now(), 'updated_at' => now()]);
                $this->cuenta['fotos_subidas']++;
            }
            $urls[] = $url;
            $huellas[] = $opt['ahash'];
        }

        return [$urls, $completas];
    }

    private function parecida(?string $h, array $huellas): bool
    {
        if (! $h) {
            return false;
        }
        foreach ($huellas as $o) {
            if ($o && OptimizadorFotos::distancia($h, $o) <= config('registro.fotos.parecidas')) {
                return true;
            }
        }

        return false;
    }

    /** Envía un lote a alojarse y anota el resultado de cada ficha. */
    private function enviar(array $lote): bool
    {
        if ($this->option('simular')) {
            foreach ($lote as $id => $l) {
                $this->line("[simulado] $id {$l['item']['nombre']} · " . count($l['item']['fotos']) . ' fotos');
            }

            return true;
        }
        try {
            $r = $this->alojarse->avisos(array_column($lote, 'item'));
        } catch (Throwable $e) {
            foreach (array_keys($lote) as $id) {
                $this->marcarError((string) $id, $e->getMessage());
            }
            $this->warn($e->getMessage());

            return false;
        }
        foreach ($r['resultados'] ?? [] as $res) {
            $id = (string) ($res['id'] ?? '');
            if (! isset($lote[$id])) {
                continue;
            }
            if (($res['estado'] ?? '') === 'error') {
                $this->marcarError($id, $res['error'] ?? 'error en alojarse');

                continue;
            }
            DB::table('registro_fichas')->where('registro_id', $id)->update([
                'estado' => $res['estado'] ?? 'enviado', 'entidad' => $res['entidad'] ?? null, 'aviso_id' => $res['aviso_id'] ?? null,
                'huella' => $lote[$id]['huella'], 'fotos' => count($lote[$id]['item']['fotos']), 'intentos' => 0, 'error' => null,
                'revisado_at' => now(), 'updated_at' => now(),
            ]);
            $this->cuenta['enviadas']++;
        }

        return true;
    }

    private function unaFicha(string $id): int
    {
        $ficha = DB::table('registro_fichas')->where('registro_id', $id)->first()
            ?? (object) ['id' => 0, 'registro_id' => $id, 'tipo' => (string) ($this->option('tipo')[0] ?? '1'), 'url' => config('registro.base') . "/$id-", 'huella' => null, 'estado' => 'pendiente'];
        $item = $this->armarItem($ficha);
        if (! $item) {
            $this->warn('La ficha no existe.');

            return self::FAILURE;
        }
        $mostrar = $item;
        $mostrar['telefono'] = $item['telefono'] ? '(privado)' : '';
        $mostrar['email'] = $item['email'] ? '(privado)' : '';
        $this->line(json_encode($mostrar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if (! $this->option('simular') && $ficha->id) {
            $this->enviar([$id => ['item' => $item, 'huella' => sha1(json_encode($item))]]);
        }

        return self::SUCCESS;
    }

    private function marcarError(string $id, string $mensaje): void
    {
        $this->cuenta['errores']++;
        DB::table('registro_fichas')->where('registro_id', $id)->update([
            'estado' => 'error', 'error' => mb_substr($mensaje, 0, 250), 'intentos' => DB::raw('intentos + 1'), 'revisado_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** GET con pausa entre peticiones. Devuelve [código, cuerpo] (cuerpo en UTF-8 salvo que sea binario). */
    private function leer(string $url, bool $binario = false): array
    {
        usleep(config('registro.pausa_ms') * 1000);
        try {
            $r = Http::withHeaders(['User-Agent' => config('registro.agente')])->timeout(40)
                ->retry(2, 3000, fn ($e) => ! ($e instanceof \Illuminate\Http\Client\RequestException) || $e->response->serverError(), throw: false)
                ->get($url);
        } catch (Throwable) {
            return [0, ''];
        }
        $cuerpo = $r->body();
        if ($binario) {
            $tipo = strtolower((string) $r->header('Content-Type'));

            return [$r->status(), $r->ok() && ($tipo === '' || str_starts_with($tipo, 'image/') || str_contains($tipo, 'octet-stream')) && strlen($cuerpo) <= 15 * 1024 * 1024 ? $cuerpo : ''];
        }
        if (! mb_check_encoding($cuerpo, 'UTF-8')) {
            $cuerpo = mb_convert_encoding($cuerpo, 'UTF-8', 'Windows-1252');
        }

        return [$r->status(), $cuerpo];
    }

    private function hayTiempo(): bool
    {
        return microtime(true) < $this->limite;
    }

    private function resumenEstados(): void
    {
        $filas = DB::table('registro_fichas')->select('estado', DB::raw('count(*) as n'))->groupBy('estado')->pluck('n', 'estado')->all();
        $this->line('Fichas por estado: ' . json_encode($filas, JSON_UNESCAPED_UNICODE));
    }
}
