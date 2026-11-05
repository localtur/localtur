<?php

namespace App\Support;

class Marca
{
    protected static ?array $actual = null;

    /** Marca activa según el host de la petición (o la de por defecto). */
    public static function actual(): array
    {
        if (static::$actual !== null) {
            return static::$actual;
        }
        $host = request()?->getHost();
        $marcas = config('marca.marcas');
        foreach ($marcas as $clave => $m) {
            if ($host && in_array($host, $m['hosts'], true)) {
                return static::$actual = $m + ['clave' => $clave];
            }
        }
        $clave = config('marca.por_defecto');

        return static::$actual = ($marcas[$clave] ?? reset($marcas)) + ['clave' => $clave];
    }

    public static function reiniciar(): void
    {
        static::$actual = null;
    }

    public static function categoria(string $clave): array
    {
        return config("marca.categorias.$clave") + ['clave' => $clave];
    }

    /** Busca la clave interna de categoría a partir del slug de la URL. */
    public static function categoriaPorSlug(string $slug): ?string
    {
        foreach (config('marca.categorias') as $clave => $c) {
            if ($c['slug'] === $slug) {
                return $clave;
            }
        }

        return null;
    }

    public static function indexar(): bool
    {
        return (bool) config('marca.indexar');
    }
}
