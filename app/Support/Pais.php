<?php

namespace App\Support;

/**
 * País activo de la petición. En localtur sale de la carpeta de la URL (/cl, /pe, /co);
 * en una marca de un solo país (alojarse.cl) viene fijo desde config/marca.php.
 */
class Pais
{
    protected static ?string $actual = null;

    public static function fijar(string $codigo): void
    {
        static::$actual = $codigo;
    }

    public static function reiniciar(): void
    {
        static::$actual = null;
    }

    public static function codigo(): string
    {
        return static::$actual ?? (Marca::actual()['pais_fijo'] ?? 'cl');
    }

    public static function actual(): array
    {
        return static::datos(static::codigo());
    }

    public static function datos(string $codigo): array
    {
        return (config("paises.$codigo") ?? []) + ['codigo' => $codigo];
    }

    /** @return array<string, array> */
    public static function todos(): array
    {
        $r = [];
        foreach (config('paises') as $c => $p) {
            $r[$c] = $p + ['codigo' => $c];
        }

        return $r;
    }

    public static function codigos(): array
    {
        return array_keys(config('paises'));
    }

    /** ¿La marca activa usa carpeta de país en la URL? */
    public static function multipais(): bool
    {
        return empty(Marca::actual()['pais_fijo']);
    }

    /**
     * URL de una ruta del sitio público respetando marca y país:
     *  localtur  → /cl/alojamiento/puerto-varas
     *  alojarse  → /alojamiento/puerto-varas
     */
    public static function ruta(string $nombre, array $params = [], ?string $pais = null): string
    {
        if (static::multipais()) {
            return route($nombre, array_merge(['pais' => $pais ?? static::codigo()], $params));
        }

        // relativa al host actual: alojarse.cl o su dominio de prueba
        return url(route(Marca::actual()['clave'].'.'.$nombre, $params, false));
    }

    /** ¿La marca $clave muestra esta región de este país? */
    public static function marcaMuestra(string $clave, string $pais, ?string $region): bool
    {
        $m = config("marca.marcas.$clave");
        if (! $m || (! empty($m['pais_fijo']) && $m['pais_fijo'] !== $pais)) {
            return false;
        }
        $f = $m['regiones'][$pais] ?? [];
        if (! empty($f['solo']) && ! in_array($region, $f['solo'], true)) {
            return false;
        }

        return ! (! empty($f['excluir']) && in_array($region, $f['excluir'], true));
    }

    /** Dominio donde se ve hoy una marca (el de prueba mientras el sitio no se indexa). */
    public static function dominio(string $clave): string
    {
        $m = config("marca.marcas.$clave");

        return Marca::indexar() ? $m['dominio'] : ($m['dominio_prueba'] ?? $m['dominio']);
    }

    /**
     * URL pública de una ruta en la marca que corresponde a esa región
     * (p. ej. desde localtur.com/cl, un aviso de Puerto Varas vive en alojarse.cl).
     */
    public static function rutaParaRegion(string $nombre, array $params, string $pais, ?string $region): string
    {
        $actual = Marca::actual()['clave'];
        if (static::marcaMuestra($actual, $pais, $region)) {
            return static::ruta($nombre, $params, $pais);
        }
        foreach (config('marca.marcas') as $clave => $m) {
            if ($clave !== $actual && static::marcaMuestra($clave, $pais, $region)) {
                $ruta = empty($m['pais_fijo'])
                    ? route($nombre, array_merge(['pais' => $pais], $params), false)
                    : route("$clave.$nombre", $params, false);

                return 'https://'.static::dominio($clave).$ruta;
            }
        }

        return static::ruta($nombre, $params, $pais);
    }

    /** Otras marcas que cubren el país activo (para el enlace "el resto de Chile está en …"). */
    public static function otrasMarcas(): array
    {
        $r = [];
        $pais = static::codigo();
        $actual = Marca::actual()['clave'];
        foreach (config('marca.marcas') as $clave => $m) {
            if ($clave === $actual || (! empty($m['pais_fijo']) && $m['pais_fijo'] !== $pais)) {
                continue;
            }
            if (empty($m['pais_fijo']) && ! isset($m['regiones'][$pais])) {
                continue;
            }
            $inicio = empty($m['pais_fijo']) ? route('inicio', ['pais' => $pais], false) : '/';
            $r[] = ['nombre' => $m['nombre'], 'url' => 'https://'.static::dominio($clave).$inicio, 'regiones' => $m['regiones'][$pais] ?? []];
        }

        return $r;
    }
}
