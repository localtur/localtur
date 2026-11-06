<?php

namespace App\Console\Commands;

use App\Models\Aviso;
use App\Models\AvisoPrivado;
use App\Models\Comuna;
use App\Models\Localidad;
use App\Models\Pais;
use App\Models\Region;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importa la semilla de avisos (dos CSV separados por punto y coma, UTF-8):
 *  - público: id_semilla;categoria;subtipo;titulo;region;comuna;localidad;direccion;estado;revisar
 *  - privado: id_semilla;fuente_archivo;clase_original;nombre_original;razon_social;rut;telefono_original;telefono_normalizado;web;destino;estado_telefono
 *
 * Los archivos NO van al repositorio (traen datos personales). Se suben al servidor a storage/app/private/ (esa carpeta no se publica ni se versiona).
 */
class ImportarSemilla extends Command
{
    protected $signature = 'localtur:importar-semilla
        {publico=storage/app/private/semilla_avisos_publico.csv : Ruta al CSV público}
        {privado=storage/app/private/semilla_avisos_privado.csv : Ruta al CSV privado}
        {--comuna= : Importar solo esta comuna (por nombre, ej. "Puerto Varas")}
        {--simular : Mostrar qué haría sin guardar nada}';

    protected $description = 'Importa avisos "sin activar" desde la semilla de datos públicos';

    private const CATEGORIAS = [
        'Alojar' => 'alojar', 'Comer' => 'comer', 'Qué hacer' => 'hacer', 'Comercio' => 'comercio', 'Servicios' => 'servicios',
    ];

    // Macrozona para elegir la imagen referencial
    private const MACROZONA = [
        'Arica y Parinacota' => 'norte', 'Tarapacá' => 'norte', 'Antofagasta' => 'norte', 'Atacama' => 'norte', 'Coquimbo' => 'norte',
        'Valparaíso' => 'centro', 'Metropolitana de Santiago' => 'centro', "Libertador General Bernardo O'Higgins" => 'centro', 'Maule' => 'centro', 'Ñuble' => 'centro',
        'Biobío' => 'sur', 'La Araucanía' => 'sur', 'Los Ríos' => 'sur', 'Los Lagos' => 'sur',
        'Aysén' => 'austral', 'Magallanes y la Antártica Chilena' => 'austral',
    ];

    public function handle(): int
    {
        $publico = $this->leer($this->argument('publico'));
        if ($publico === null) {
            return self::FAILURE;
        }
        $privado = [];
        if ($ruta = $this->argument('privado')) {
            foreach ($this->leer($ruta) ?? [] as $fila) {
                $privado[$fila['id_semilla']] = $fila;
            }
        }
        $soloComuna = $this->option('comuna') ? Str::slug($this->option('comuna')) : null;
        $simular = (bool) $this->option('simular');

        $chile = Pais::firstOrCreate(['codigo' => 'CL'], ['nombre' => 'Chile', 'moneda' => 'CLP']);
        $cont = ['creados' => 0, 'actualizados' => 0, 'omitidos' => 0, 'revisar' => 0];

        $avance = $this->output->createProgressBar(count($publico));
        DB::beginTransaction();
        try {
            foreach ($publico as $f) {
                $avance->advance();
                if ($soloComuna && Str::slug($f['comuna'] ?? '') !== $soloComuna) {
                    continue;
                }
                $categoria = self::CATEGORIAS[$f['categoria'] ?? ''] ?? null;
                if (! $categoria || trim($f['titulo'] ?? '') === '' || trim($f['comuna'] ?? '') === '') {
                    $cont['omitidos']++;

                    continue;
                }
                if (! empty($f['revisar'])) {
                    $cont['revisar']++;
                }
                if ($simular) {
                    $cont['creados']++;

                    continue;
                }

                $region = Region::firstOrCreate(
                    ['pais_id' => $chile->id, 'slug' => Str::slug($f['region'])],
                    ['nombre' => $f['region']]
                );
                $comuna = Comuna::firstOrCreate(
                    ['region_id' => $region->id, 'slug' => Str::slug($f['comuna'])],
                    ['nombre' => $f['comuna']]
                );
                $nombreLocalidad = trim($f['localidad'] ?? '') !== '' ? $f['localidad'] : $f['comuna'];
                $p = $privado[$f['id_semilla']] ?? [];
                $localidad = Localidad::firstOrCreate(
                    ['comuna_id' => $comuna->id, 'slug' => Str::slug($nombreLocalidad)],
                    ['nombre' => $nombreLocalidad, 'destino' => ($p['destino'] ?? '-') !== '-' ? ($p['destino'] ?? null) : null]
                );

                $existente = AvisoPrivado::where('id_semilla', $f['id_semilla'])->first()?->aviso;
                if ($existente && $existente->estado !== 'sin_activar') {
                    // El dueño ya lo activó: nunca se pisan sus datos.
                    $cont['omitidos']++;

                    continue;
                }

                $datos = [
                    'localidad_id' => $localidad->id,
                    'categoria' => $categoria,
                    'subtipo' => $f['subtipo'] ?: null,
                    'titulo' => Str::limit($f['titulo'], 60, ''),
                    'descripcion_corta' => $this->descripcionCorta($f, $localidad->nombre),
                    'direccion' => $f['direccion'] ?: null,
                    'estado' => 'sin_activar',
                    'origen' => 'registro_publico',
                    'imagen_referencial' => $this->imagenReferencial($categoria, $f['subtipo'] ?? '', $f['region']),
                    'indexable' => false, // se indexa recién con dirección exacta en el mapa
                ];

                if ($existente) {
                    $existente->update($datos);
                    $aviso = $existente;
                    $cont['actualizados']++;
                } else {
                    $aviso = Aviso::create($datos + ['slug' => $this->slugLibre($f['titulo'], $localidad->id, $categoria)]);
                    $cont['creados']++;
                }

                AvisoPrivado::updateOrCreate(['aviso_id' => $aviso->id], [
                    'id_semilla' => $f['id_semilla'],
                    'fuente' => $p['fuente_archivo'] ?? 'semilla',
                    'nombre_original' => $p['nombre_original'] ?? null,
                    'clase_original' => $p['clase_original'] ?? null,
                    'razon_social' => $p['razon_social'] ?? null,
                    'rut' => ($p['rut'] ?? '') ?: null,
                    'telefono_original' => $p['telefono_original'] ?? null,
                    'telefono' => ($p['telefono_normalizado'] ?? '') ?: null,
                    'estado_telefono' => $p['estado_telefono'] ?? null,
                    'web' => ($p['web'] ?? '') ?: null,
                    'notas' => $f['revisar'] ?: null,
                ]);
            }
            $simular ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        $avance->finish();
        $this->newLine(2);
        $this->table(['Creados', 'Actualizados', 'Omitidos', 'Con algo por revisar'], [[
            $cont['creados'], $cont['actualizados'], $cont['omitidos'], $cont['revisar'],
        ]]);
        if ($simular) {
            $this->warn('Simulación: no se guardó nada.');
        }

        return self::SUCCESS;
    }

    /** @return array<int, array<string, string>>|null */
    private function leer(string $ruta): ?array
    {
        if (! is_readable($ruta)) {
            $this->error("No se puede leer el archivo: $ruta");

            return null;
        }
        $h = fopen($ruta, 'r');
        $bom = fread($h, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($h);
        }
        $cab = array_map('trim', fgetcsv($h, 0, ';') ?: []);
        $filas = [];
        while (($r = fgetcsv($h, 0, ';')) !== false) {
            if (count(array_filter($r, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }
            $filas[] = array_combine($cab, array_pad(array_slice($r, 0, count($cab)), count($cab), ''));
        }
        fclose($h);

        return $filas;
    }

    private function slugLibre(string $titulo, int $localidadId, string $categoria): string
    {
        $base = Str::slug($titulo) ?: 'aviso';
        $slug = $base;
        $i = 2;
        while (Aviso::where('localidad_id', $localidadId)->where('categoria', $categoria)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function descripcionCorta(array $f, string $localidad): string
    {
        $subtipo = $f['subtipo'] ?: 'Servicio turístico';
        $lugar = $localidad === $f['comuna'] ? $f['comuna'] : "$localidad, {$f['comuna']}";
        $region = str_starts_with($f['region'], 'Metropolitana') ? 'Región '.$f['region'] : 'Región de '.$f['region'];
        $txt = "$subtipo en $lugar, $region. Ubicación, cómo llegar y qué hacer cerca.";

        return Str::limit($txt, 155, '');
    }

    private function imagenReferencial(string $categoria, string $subtipo, string $region): string
    {
        $zona = self::MACROZONA[$region] ?? 'centro';

        return Str::slug($categoria.'-'.($subtipo ?: 'general')).'-'.$zona;
    }
}
