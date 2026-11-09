<?php

namespace App\Registro;

/**
 * Convierte una foto a WebP liviano (lado máximo configurable) y calcula su huella visual (aHash 8×8),
 * para descartar duplicados aunque estén en otro tamaño o formato.
 */
class OptimizadorFotos
{
    public function __construct(private array $cfg) {}

    /** @return array{webp: string, ahash: string, ancho: int, alto: int}|null  null si no es una foto útil */
    public function procesar(string $bytes): ?array
    {
        if (strlen($bytes) < 2000 || ! function_exists('imagecreatefromstring')) {
            return null;
        }
        $img = @imagecreatefromstring($bytes);
        if ($img === false) {
            return null;
        }
        $w = imagesx($img);
        $h = imagesy($img);
        if (min($w, $h) < ($this->cfg['lado_min'] ?? 240) || max($w, $h) / max(1, min($w, $h)) > 4) {
            imagedestroy($img);

            return null; // ícono, logo o franja: no es una foto del lugar
        }
        if ($this->casiUniforme($img, $w, $h)) {
            imagedestroy($img);

            return null; // imagen vacía o de un solo color ("sin foto")
        }

        $ahash = $this->ahash($img, $w, $h);
        $f = min(1, ($this->cfg['lado_max'] ?? 1280) / max($w, $h));
        $nw = max(1, (int) round($w * $f));
        $nh = max(1, (int) round($h * $f));
        $lienzo = imagecreatetruecolor($nw, $nh);
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255)); // fondo blanco para PNG con transparencia
        imagecopyresampled($lienzo, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($img);

        $calidad = $this->cfg['calidad'] ?? 74;
        $webp = $this->webp($lienzo, $calidad);
        while (strlen($webp) > ($this->cfg['peso_max'] ?? 350 * 1024) && $calidad > 45) {
            $calidad -= 10;
            $webp = $this->webp($lienzo, $calidad);
        }
        imagedestroy($lienzo);

        return ['webp' => $webp, 'ahash' => $ahash, 'ancho' => $nw, 'alto' => $nh];
    }

    /** Cuántos bits difieren entre dos huellas (0 = idénticas). */
    public static function distancia(string $a, string $b): int
    {
        $d = 0;
        for ($i = 0; $i < 16; $i += 4) {
            $x = hexdec(substr($a, $i, 4)) ^ hexdec(substr($b, $i, 4));
            for (; $x; $x &= $x - 1) {
                $d++;
            }
        }

        return $d;
    }

    private function webp(\GdImage $img, int $calidad): string
    {
        ob_start();
        imagewebp($img, null, $calidad);

        return (string) ob_get_clean();
    }

    private function ahash(\GdImage $img, int $w, int $h): string
    {
        $m = imagecreatetruecolor(8, 8);
        imagecopyresampled($m, $img, 0, 0, 0, 0, 8, 8, $w, $h);
        $grises = [];
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $c = imagecolorat($m, $x, $y);
                $grises[] = (($c >> 16) & 255) * 0.299 + (($c >> 8) & 255) * 0.587 + ($c & 255) * 0.114;
            }
        }
        imagedestroy($m);
        $prom = array_sum($grises) / 64;
        $hex = '';
        foreach (array_chunk($grises, 4) as $grupo) {
            $n = 0;
            foreach ($grupo as $g) {
                $n = ($n << 1) | ($g >= $prom ? 1 : 0);
            }
            $hex .= dechex($n);
        }

        return $hex;
    }

    private function casiUniforme(\GdImage $img, int $w, int $h): bool
    {
        $m = imagecreatetruecolor(16, 16);
        imagecopyresampled($m, $img, 0, 0, 0, 0, 16, 16, $w, $h);
        $v = [];
        for ($y = 0; $y < 16; $y++) {
            for ($x = 0; $x < 16; $x++) {
                $c = imagecolorat($m, $x, $y);
                $v[] = (($c >> 16) & 255) + (($c >> 8) & 255) + ($c & 255);
            }
        }
        imagedestroy($m);
        $prom = array_sum($v) / count($v);
        $var = array_sum(array_map(fn ($x) => ($x - $prom) ** 2, $v)) / count($v);

        return sqrt($var) < 12;
    }
}
