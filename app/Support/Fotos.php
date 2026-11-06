<?php

namespace App\Support;

use App\Models\Aviso;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Guarda fotos de avisos en el disco público: máx. 1600 px por lado, en WebP cuando el servidor lo permite. */
class Fotos
{
    public const LADO_MAX = 1600;

    public static function guardar(UploadedFile $archivo, Aviso $aviso): string
    {
        $dir = 'avisos/'.$aviso->id;
        $nombre = Str::random(20);
        $datos = @file_get_contents($archivo->getRealPath());
        $img = $datos !== false && function_exists('imagecreatefromstring') ? @imagecreatefromstring($datos) : false;

        if ($img === false) {
            // Sin GD: se guarda tal cual (ya se validó que es imagen)
            return $archivo->storeAs($dir, $nombre.'.'.$archivo->extension(), 'public');
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $f = min(1, self::LADO_MAX / max($w, $h));
        if ($f < 1) {
            $nw = (int) round($w * $f);
            $nh = (int) round($h * $f);
            $nueva = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($nueva, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($img);
            $img = $nueva;
        }

        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($img, null, 82);
            $ext = 'webp';
        } else {
            imagejpeg($img, null, 85);
            $ext = 'jpg';
        }
        $bin = ob_get_clean();
        imagedestroy($img);
        $ruta = "$dir/$nombre.$ext";
        Storage::disk('public')->put($ruta, $bin);

        return $ruta;
    }

    public static function borrar(string $ruta): void
    {
        if (str_starts_with($ruta, 'avisos/')) {
            Storage::disk('public')->delete($ruta);
        }
    }
}
