<?php

namespace App\Registro;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Lee las páginas del registro público de prestadores turísticos (serviciosturisticos.sernatur.cl).
 * Mismos selectores que el importador anterior de alojarse (base44/shared/sernaturDetailParser.ts).
 */
class Parser
{
    public function __construct(private string $base = 'https://serviciosturisticos.sernatur.cl') {}

    /** Fichas de una página de resultados: [['id' => '25890', 'url' => 'https://…/25890-hostel-x'], …] */
    public function listado(string $html): array
    {
        $x = $this->xpath($html);
        $fichas = [];
        foreach ($x->query('//a[@href]') as $a) {
            $href = trim($a->getAttribute('href'));
            if (preg_match('~^(?:https?://[^/]+)?/(\d+)-[\w-]+/?$~u', $href, $m)) {
                $fichas[$m[1]] ??= ['id' => $m[1], 'url' => $this->absoluta(rtrim($href, '/'))];
            }
        }

        return array_values($fichas);
    }

    /** Total de servicios que informa la búsqueda ("1.234 Servicios"), o null. */
    public function total(string $html): ?int
    {
        return preg_match('/([\d.]+)\s*Servicios/iu', strip_tags($html), $m) ? (int) str_replace('.', '', $m[1]) : null;
    }

    /** Datos de una ficha. Las fotos vienen en orden: la de perfil primero y después la galería. */
    public function detalle(string $html, string $url): array
    {
        $x = $this->xpath($html);
        $info = $x->query('//*[' . $this->clase('col-md-7') . ']')->item(0) ?? $x->document->documentElement;
        $texto = fn (string $q) => $this->limpio($x->query($q, $info)->item(0)?->textContent);

        $d = [
            'id' => preg_match('~/(\d+)-~', $url, $m) ? $m[1] : null,
            'url' => $url,
            'nombre' => $texto('.//*[' . $this->clase('tituloperfil') . ']'),
            'clasificacion' => $texto('.//h5'),
            'region' => $texto('.//*[' . $this->clase('region') . ']'),
            'direccion' => '', 'localidad' => '', 'comuna' => '', 'telefono' => '', 'email' => '',
        ];

        $campos = ['dirección' => 'direccion', 'direccion' => 'direccion', 'localidad' => 'localidad', 'comuna' => 'comuna',
            'teléfono' => 'telefono', 'telefono' => 'telefono', 'email' => 'email', 'correo' => 'email'];
        foreach ($x->query('.//*[' . $this->clase('textos') . ']//p', $info) as $p) {
            $todo = mb_strtolower($this->limpio($p->textContent));
            $valor = $this->limpio($x->query('.//b', $p)->item(0)?->textContent);
            foreach ($campos as $etiqueta => $campo) {
                if (str_starts_with($todo, $etiqueta) || str_contains($todo, $etiqueta . ':')) {
                    $d[$campo] = $d[$campo] ?: $valor;
                    break;
                }
            }
        }
        $d['email'] = mb_strtolower($d['email']);
        if (! $d['comuna'] && str_contains($d['direccion'], ',')) {
            $d['comuna'] = trim(substr($d['direccion'], strrpos($d['direccion'], ',') + 1));
        }
        if (! $d['comuna']) {
            $d['comuna'] = $d['localidad'];
        }

        // Fotos: la de perfil y la galería (si el enlace apunta a una versión más grande, se usa esa)
        $fotos = [];
        foreach ($x->query('//*[' . $this->clase('foto-perfil') . ']//img | //*[' . $this->clase('galeria-dinamica') . ']//img') as $img) {
            /** @var DOMElement $img */
            $src = $img->getAttribute('data-src') ?: $img->getAttribute('src');
            $padre = $img->parentNode instanceof DOMElement && $img->parentNode->nodeName === 'a' ? $img->parentNode->getAttribute('href') : '';
            if ($padre && preg_match('/\.(jpe?g|png|webp|gif)(\?.*)?$/i', $padre)) {
                $src = $padre;
            }
            if ($src && ! $this->esSinFoto($src)) {
                $fotos[] = $this->absoluta($src);
            }
        }
        $d['fotos'] = array_values(array_unique($fotos));

        // Estado de la inscripción: "Registro vigente" / "Registro no vigente"
        $d['vigente'] = null;
        foreach ($x->query('//*[' . $this->clase('textos') . ']//b') as $b) {
            $t = mb_strtolower($this->limpio($b->textContent));
            if (str_contains($t, 'vigente')) {
                $d['vigente'] = ! str_contains($t, 'no vigente');
            }
        }

        [$d['lat'], $d['lng']] = $this->coordenadas($html) ?? [null, null];

        return $d;
    }

    /** Coordenadas de un mapa embebido en la ficha, si las hay. */
    public function coordenadas(string $html): ?array
    {
        foreach (['/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', '/center=(-?\d+\.\d+),\s*(-?\d+\.\d+)/', '/[?&]q=(-?\d+\.\d+),\s*(-?\d+\.\d+)/',
            '/LatLng\(\s*(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)\s*\)/', '/data-lat=["\'](-?\d+\.\d+)["\'][^>]*data-lng=["\'](-?\d+\.\d+)["\']/'] as $re) {
            if (preg_match($re, $html, $m)) {
                $lat = (float) $m[1];
                $lng = (float) $m[2];
                // Solo Chile continental e insular
                if ($lat < -17 && $lat > -56.5 && $lng < -66 && $lng > -110) {
                    return [$lat, $lng];
                }
            }
        }

        return null;
    }

    public function esSinFoto(string $src): bool
    {
        return (bool) preg_match('/sin[-_]?foto|no[-_]?image|placeholder|default\.(png|jpe?g)|logo[-_]sernatur/i', $src);
    }

    private function xpath(string $html): DOMXPath
    {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        return new DOMXPath($doc);
    }

    private function clase(string $c): string
    {
        return "contains(concat(' ', normalize-space(@class), ' '), ' $c ')";
    }

    private function limpio(?string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private function absoluta(string $u): string
    {
        $u = trim($u);
        if (str_starts_with($u, '//')) {
            return 'https:' . $u;
        }
        if (preg_match('~^https?://~i', $u)) {
            return preg_replace('~^http://~i', 'https://', $u);
        }

        return rtrim($this->base, '/') . '/' . ltrim($u, '/');
    }
}
