<?php

namespace App\Support;

use App\Models\Aviso;

/**
 * Ilustración referencial para avisos sin fotos: un paisaje de la macrozona (norte, centro, sur, austral)
 * con un ícono de la categoría. SVG propio, generado en el servidor; la variación sale del slug del aviso,
 * así cada aviso tiene siempre "su" ilustración y dos avisos vecinos rara vez se ven iguales.
 */
class Ilustracion
{
    private const CIELOS = [
        // mañana, mediodía, atardecer
        ['#cfe8f3', '#f4f1e6', '#f6d38a'],
        ['#8fc6e8', '#d9eef7', '#fff6c9'],
        ['#f0a06b', '#f7d3a1', '#ffe9b8'],
    ];

    private const ZONAS = [
        'norte' => ['lejos' => '#d9a37a', 'medio' => '#c27f55', 'suelo' => '#e7c08f', 'agua' => null],
        'centro' => ['lejos' => '#a9b9c9', 'medio' => '#7d9a6a', 'suelo' => '#a6bb72', 'agua' => null],
        'sur' => ['lejos' => '#9fb4c7', 'medio' => '#4f7a5a', 'suelo' => '#6f9a5c', 'agua' => '#5b9ab8'],
        'austral' => ['lejos' => '#b8c4d2', 'medio' => '#5d7184', 'suelo' => '#8d9a6c', 'agua' => '#6fa8c0'],
    ];

    /**
     * Imágenes referenciales (webp) en public/img/referenciales/. Si existe una subcarpeta con la clave de
     * la categoría (alojar, comer, hacer, comercio, servicios), se usan esas; si no, las generales.
     * Para agregar más basta con dejar archivos en la carpeta.
     *
     * @return array<int, string> URLs públicas
     */
    public static function imagenes(string $categoria): array
    {
        static $cache = [];
        if (! isset($cache[$categoria])) {
            $base = public_path('img/referenciales');
            $lista = glob("$base/$categoria/*.webp") ?: glob("$base/*.webp") ?: [];
            sort($lista);
            $cache[$categoria] = array_map(fn ($f) => asset('img/referenciales/'.ltrim(substr($f, strlen($base)), '/')), $lista);
        }

        return $cache[$categoria];
    }

    /** Una imagen fija por aviso (para tarjetas), o null si no hay imágenes cargadas. */
    public static function imagenDe(Aviso $a, int $desfase = 0): ?string
    {
        $l = self::imagenes($a->categoria);

        return $l ? $l[(crc32($a->slug.'|'.$a->id) + $desfase) % count($l)] : null;
    }

    public static function zona(Aviso $a): string
    {
        $z = $a->imagen_referencial ? substr($a->imagen_referencial, strrpos($a->imagen_referencial, '-') + 1) : '';

        return isset(self::ZONAS[$z]) ? $z : 'centro';
    }

    /** $variante cambia el paisaje (cielo, montañas, orilla); el ícono de la categoría se mantiene. */
    public static function svg(Aviso $a, string $clase = '', int $variante = 0): string
    {
        $semilla = crc32($a->slug.'|'.$a->id.'|'.$variante);
        $r = function (int $min, int $max) use (&$semilla): int {
            $semilla = ($semilla * 1103515245 + 12345) & 0x7FFFFFFF;

            return $min + ($semilla % ($max - $min + 1));
        };
        $zona = self::zona($a);
        $z = self::ZONAS[$zona];
        $cielo = self::CIELOS[$r(0, 2)];
        $id = 'i'.dechex(crc32($a->slug.$a->id.'|'.$variante));

        $o = [];
        $o[] = "<svg class=\"$clase\" viewBox=\"0 0 800 450\" xmlns=\"http://www.w3.org/2000/svg\" role=\"img\" aria-label=\"Ilustración referencial\" preserveAspectRatio=\"xMidYMid slice\">";
        $o[] = "<defs><linearGradient id=\"$id\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\"><stop offset=\"0\" stop-color=\"{$cielo[0]}\"/><stop offset=\".6\" stop-color=\"{$cielo[1]}\"/><stop offset=\"1\" stop-color=\"{$cielo[2]}\"/></linearGradient></defs>";
        $o[] = "<rect width=\"800\" height=\"450\" fill=\"url(#$id)\"/>";
        $o[] = '<circle cx="'.$r(80, 720).'" cy="'.$r(60, 140).'" r="'.$r(26, 42).'" fill="#fff8e1" opacity=".85"/>';

        // Montañas lejanas
        $o[] = '<path d="'.self::cordillera($r, 250, $zona === 'norte' ? 60 : 140, $zona === 'austral' ? 14 : 7).'" fill="'.$z['lejos'].'"/>';

        // Rasgo de la zona
        if ($zona === 'sur') {
            $x = $r(380, 620);
            $o[] = "<path d=\"M".($x - 190)." 300 L$x 120 L".($x + 190)." 300 Z\" fill=\"#8ea4b6\"/>";
            $o[] = "<path d=\"M".($x - 52)." 169 L$x 120 L".($x + 52)." 169 L".($x + 30)." 160 L".($x + 12)." 172 L".($x - 10)." 160 L".($x - 30)." 172 Z\" fill=\"#ffffff\"/>";
        } elseif ($zona === 'austral') {
            $x = $r(250, 550);
            foreach ([0, 46, 88] as $i => $dx) {
                $h = [150, 120, 160][$i];
                $o[] = '<path d="M'.($x + $dx - 22).' 300 L'.($x + $dx - 6)." $h L".($x + $dx + 8).' '.($h + 4).' L'.($x + $dx + 24).' 300 Z" fill="#6e7f90"/>';
                $o[] = '<path d="M'.($x + $dx - 10).' '.($h + 22).' L'.($x + $dx - 6)." $h L".($x + $dx + 8).' '.($h + 4).' L'.($x + $dx + 11).' '.($h + 24).' Z" fill="#fff"/>';
            }
        }

        // Montañas medias
        $o[] = '<path d="'.self::cordillera($r, 300, $zona === 'norte' ? 40 : 90, 5).'" fill="'.$z['medio'].'"/>';

        // Agua o suelo
        if ($z['agua']) {
            $o[] = '<rect y="300" width="800" height="70" fill="'.$z['agua'].'"/>';
            for ($i = 0; $i < 6; $i++) {
                $y = $r(310, 360);
                $x = $r(0, 700);
                $o[] = "<rect x=\"$x\" y=\"$y\" width=\"".$r(40, 110).'" height="3" rx="1.5" fill="#fff" opacity=".35"/>';
            }
            $o[] = '<path d="M0 370 Q200 '.$r(350, 365).' 400 370 T800 368 V450 H0 Z" fill="'.$z['suelo'].'"/>';
        } else {
            $o[] = '<path d="M0 320 Q200 '.$r(295, 315).' 400 322 T800 318 V450 H0 Z" fill="'.$z['suelo'].'"/>';
            if ($zona === 'centro') {
                for ($i = 0; $i < 7; $i++) {
                    $o[] = '<path d="M'.(440 + $i * 50).' 450 L'.(560 + $i * 34).' 335" stroke="#5f7d45" stroke-width="5" opacity=".55"/>';
                }
            } else {
                for ($i = 0; $i < 3; $i++) {
                    $x = $r(500, 760);
                    $o[] = "<path d=\"M$x 400 V350 M$x 372 h-12 v-14 M$x 365 h11 v-16\" stroke=\"#6f8a4a\" stroke-width=\"7\" stroke-linecap=\"round\" fill=\"none\"/>";
                }
            }
        }

        // Árboles (no en el norte)
        if ($zona !== 'norte') {
            for ($i = 0; $i < 4; $i++) {
                $x = $r(20, 300);
                $h = $r(50, 80);
                $o[] = "<path d=\"M".($x - 18).' 415 L'.$x.' '.(415 - $h).' L'.($x + 18).' 415 Z" fill="#3f6b47"/><rect x="'.($x - 3).'" y="415" width="6" height="12" fill="#6b4f36"/>';
            }
        }

        $o[] = self::icono($a);
        $o[] = '</svg>';

        return implode('', $o);
    }

    private static function cordillera(callable $r, int $base, int $altura, int $picos): string
    {
        $d = "M0 $base";
        $paso = 800 / $picos;
        for ($i = 0; $i <= $picos; $i++) {
            $x = (int) round($i * $paso);
            $y = $base - $r((int) ($altura * 0.4), $altura);
            $d .= ' L'.($x - (int) ($paso / 2)).' '.($base - $r(0, (int) ($altura * 0.3))).' L'.$x.' '.$y;
        }

        return $d." L800 $base V450 H0 Z";
    }

    /** Ícono de la categoría en primer plano, centrado. */
    private static function icono(Aviso $a): string
    {
        $sub = mb_strtolower((string) $a->subtipo);
        $g = '<g transform="translate(400 340) scale(1.15)">';
        $fin = '</g>';
        $muro = '#f4ece0';
        $techo = '#b5532e';

        if ($a->categoria === 'alojar') {
            if (str_contains($sub, 'camping')) {
                return $g.'<path d="M-70 60 L0 -40 L70 60 Z" fill="#d9822b"/><path d="M-14 60 L0 10 L14 60 Z" fill="#7a3f12"/><path d="M0 -40 V-58" stroke="#5a4636" stroke-width="4"/>'.$fin;
            }
            if (str_contains($sub, 'hotel') || str_contains($sub, 'apart')) {
                $v = '';
                for ($f = 0; $f < 4; $f++) {
                    for ($c = 0; $c < 4; $c++) {
                        $v .= '<rect x="'.(-48 + $c * 26).'" y="'.(-62 + $f * 28).'" width="14" height="16" fill="#9cc3d5"/>';
                    }
                }

                return $g.'<rect x="-60" y="-80" width="120" height="140" fill="'.$muro.'"/>'.$v.'<rect x="-12" y="40" width="24" height="20" fill="#6b4f36"/>'.$fin;
            }
            // cabañas, hostal, residencial y el resto: una casa con techo a dos aguas
            return $g.'<path d="M-80 0 L0 -62 L80 0 Z" fill="'.$techo.'"/><rect x="-64" y="0" width="128" height="60" fill="#c9935f"/>'
                .'<path d="M-64 15 H64 M-64 30 H64 M-64 45 H64" stroke="#a87443" stroke-width="2"/>'
                .'<rect x="-14" y="18" width="28" height="42" fill="#6b4f36"/><rect x="-50" y="14" width="22" height="18" fill="#fff2c4"/><rect x="28" y="14" width="22" height="18" fill="#fff2c4"/>'
                .'<rect x="40" y="-52" width="12" height="28" fill="#7a6656"/>'.$fin;
        }
        if ($a->categoria === 'comer') {
            return $g.'<ellipse cx="0" cy="40" rx="80" ry="18" fill="#fff"/><ellipse cx="0" cy="34" rx="58" ry="11" fill="#f1e4cf"/>'
                .'<path d="M-40 34 Q0 -30 40 34 Z" fill="#c9935f"/><path d="M-100 -30 V58 M-108 -30 V-4 M-92 -30 V-4 M-108 -4 H-92" stroke="#5a4636" stroke-width="5" stroke-linecap="round"/>'
                .'<path d="M100 -30 Q114 0 100 14 V58" stroke="#5a4636" stroke-width="5" fill="none" stroke-linecap="round"/>'.$fin;
        }
        if ($a->categoria === 'hacer') {
            return $g.'<path d="M-110 40 Q0 68 110 40 Q0 52 -110 40 Z" fill="#e0662f"/><circle cx="0" cy="8" r="11" fill="#3b2f2a"/><path d="M0 19 V40" stroke="#3b2f2a" stroke-width="10"/>'
                .'<path d="M-70 -20 L70 50" stroke="#5a4636" stroke-width="5" stroke-linecap="round"/><ellipse cx="-72" cy="-21" rx="14" ry="6" transform="rotate(27 -72 -21)" fill="#f2c14e"/><ellipse cx="72" cy="51" rx="14" ry="6" transform="rotate(27 72 51)" fill="#f2c14e"/>'.$fin;
        }
        if ($a->categoria === 'comercio') {
            $toldo = '';
            for ($i = 0; $i < 6; $i++) {
                $toldo .= '<path d="M'.(-72 + $i * 24).' -30 h24 v18 a12 12 0 0 1 -24 0 Z" fill="'.($i % 2 ? '#fff' : '#c8642c').'"/>';
            }

            return $g.'<rect x="-72" y="-12" width="144" height="72" fill="'.$muro.'"/>'.$toldo.'<rect x="-56" y="10" width="60" height="34" fill="#9cc3d5"/><rect x="18" y="6" width="36" height="54" fill="#6b4f36"/>'.$fin;
        }

        // servicios
        return $g.'<circle cx="0" cy="10" r="52" fill="#f4ece0"/><path d="M-26 36 L18 -8 A18 18 0 1 0 6 -20 L-38 24 Z" fill="#5d6b68"/>'.$fin;
    }
}
