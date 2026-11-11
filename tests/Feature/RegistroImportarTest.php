<?php

namespace Tests\Feature;

use App\Registro\OptimizadorFotos;
use App\Registro\Parser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegistroImportarTest extends TestCase
{
    use RefreshDatabase;

    private function html(string $f): string
    {
        return file_get_contents(base_path("tests/fixtures/registro/$f"));
    }

    /** Foto de prueba con textura (no uniforme), en JPEG. */
    private function foto(int $w, int $h, int $semilla): string
    {
        mt_srand($semilla);
        $img = imagecreatetruecolor($w, $h);
        for ($i = 0; $i < 40; $i++) {
            imagefilledrectangle($img, mt_rand(0, $w), mt_rand(0, $h), mt_rand(0, $w), mt_rand(0, $h), imagecolorallocate($img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
        }
        ob_start();
        imagejpeg($img, null, 90);

        return ob_get_clean();
    }

    private function escalar(string $jpg, int $w, int $h): string
    {
        $o = imagecreatefromstring($jpg);
        $n = imagecreatetruecolor($w, $h);
        imagecopyresampled($n, $o, 0, 0, 0, 0, $w, $h, imagesx($o), imagesy($o));
        ob_start();
        imagepng($n);

        return ob_get_clean();
    }

    public function test_lee_listado_y_ficha(): void
    {
        $p = new Parser();
        $l = $p->listado($this->html('listado.html'));
        $this->assertSame(['25890', '31002'], array_column($l, 'id'));
        $this->assertSame('https://serviciosturisticos.sernatur.cl/25890-hostel-piedras-rojas', $l[0]['url']);
        $this->assertSame(2, $p->total($this->html('listado.html')));

        $d = $p->detalle($this->html('ficha.html'), 'https://serviciosturisticos.sernatur.cl/31002-cabanas-el-arrayan');
        $this->assertSame('31002', $d['id']);
        $this->assertSame('Cabañas El Arrayán', $d['nombre']);
        $this->assertSame('Cabaña', $d['clasificacion']);
        $this->assertSame('Región de Los Lagos', $d['region']);
        $this->assertSame('Puerto Varas', $d['comuna']);
        $this->assertSame('Ensenada', $d['localidad']);
        $this->assertSame('contacto@arrayan.cl', $d['email']);
        $this->assertTrue($d['vigente']);
        $this->assertSame([-41.2153, -72.5361], [$d['lat'], $d['lng']]);
        $this->assertSame([
            'https://serviciosturisticos.sernatur.cl/fotos/perfil.jpg',
            'https://serviciosturisticos.sernatur.cl/fotos/g1-grande.jpg',
            'https://serviciosturisticos.sernatur.cl/fotos/g2.jpg',
            'https://serviciosturisticos.sernatur.cl/fotos/g3.jpg',
            'https://serviciosturisticos.sernatur.cl/fotos/logo.png',
        ], $d['fotos']);
    }

    public function test_optimiza_y_reconoce_duplicados(): void
    {
        $o = new OptimizadorFotos(config('registro.fotos'));
        $a = $o->procesar($this->foto(2400, 1600, 1));
        $this->assertNotNull($a);
        $this->assertSame('RIFF', substr($a['webp'], 0, 4));
        $this->assertSame(1280, $a['ancho']);
        $this->assertLessThanOrEqual(350 * 1024, strlen($a['webp']));
        $b = $o->procesar($this->escalar($this->foto(2400, 1600, 1), 800, 533));
        $this->assertLessThanOrEqual(5, OptimizadorFotos::distancia($a['ahash'], $b['ahash']), 'la misma foto en otro tamaño es duplicada');
        $c = $o->procesar($this->foto(1600, 1200, 7));
        $this->assertGreaterThan(5, OptimizadorFotos::distancia($a['ahash'], $c['ahash']));
        $this->assertNull($o->procesar($this->foto(120, 120, 3)), 'íconos chicos se descartan');
        $gris = imagecreatetruecolor(900, 600);
        imagefill($gris, 0, 0, imagecolorallocate($gris, 200, 200, 200));
        ob_start();
        imagejpeg($gris);
        $this->assertNull($o->procesar(ob_get_clean()), 'imagen vacía se descarta');
    }

    public function test_importa_y_envia_a_alojarse(): void
    {
        config(['registro.pausa_ms' => 0, 'registro.geocodificar' => false, 'registro.alojarse.secreto' => 'sek', 'registro.tipos' => ['1' => 'Alojamiento'], 'registro.fotos.subir' => true]);
        $perfil = $this->foto(1600, 1200, 11);
        $enviados = [];
        $subidas = 0;
        Http::fake(function (Request $r) use ($perfil, &$enviados, &$subidas) {
            $u = $r->url();
            if (str_contains($u, 'alojarse.cl')) {
                $this->assertSame('sek', $r->header('x-cron-secret')[0]);
                if ($r['accion'] === 'subir_foto') {
                    $subidas++;
                    $this->assertSame('RIFF', substr(base64_decode($r['base64']), 0, 4));

                    return Http::response(['url' => "https://base44.app/f/$subidas.webp"]);
                }
                $enviados = array_merge($enviados, $r['items']);

                return Http::response(['resultados' => array_map(fn ($i) => ['id' => $i['id'], 'estado' => 'creado', 'aviso_id' => 'c' . $i['id'], 'entidad' => 'Cabin'], $r['items'])]);
            }
            if (str_contains($u, 'resultado.php')) {
                return Http::response(str_contains($u, 'page=1') ? $this->html('listado.html') : '<html>0 Servicios</html>');
            }
            if (str_contains($u, '/fotos/perfil.jpg') || str_contains($u, '/fotos/g2.jpg')) {
                return Http::response($perfil, 200, ['Content-Type' => 'image/jpeg']); // g2 = misma foto que el perfil
            }
            if (str_contains($u, '/fotos/g1-grande.jpg')) {
                return Http::response($this->foto(1200, 900, 12), 200, ['Content-Type' => 'image/jpeg']);
            }
            if (str_contains($u, '/fotos/g3.jpg')) {
                return Http::response($this->escalar($perfil, 640, 480), 200, ['Content-Type' => 'image/png']); // perfil achicado
            }
            if (str_contains($u, '/fotos/logo.png')) {
                return Http::response($this->foto(150, 60, 5), 200, ['Content-Type' => 'image/png']);
            }
            if (str_contains($u, '25890')) {
                return Http::response('no', 404);
            }

            return Http::response($this->html('ficha.html'));
        });

        $this->artisan('registro:importar')->assertSuccessful();

        $this->assertSame(2, DB::table('registro_fichas')->count());
        $this->assertSame('no_encontrada', DB::table('registro_fichas')->where('registro_id', '25890')->value('estado'));
        $this->assertCount(1, $enviados);
        $i = $enviados[0];
        $this->assertSame('31002', $i['id']);
        $this->assertSame('1', $i['tipo']);
        $this->assertSame(['https://base44.app/f/1.webp', 'https://base44.app/f/2.webp'], $i['fotos'], 'perfil + g1; g2 y g3 son la misma foto, el logo y "sin foto" se descartan');
        $this->assertSame(2, $subidas);
        $this->assertTrue($i['fotos_completas']);
        $this->assertArrayNotHasKey('web', $i, 'el sitio web no se importa');
        $ficha = DB::table('registro_fichas')->where('registro_id', '31002')->first();
        $this->assertSame('creado', $ficha->estado);
        $this->assertSame('c31002', $ficha->aviso_id);
        $this->assertSame(2, (int) $ficha->fotos);
        $this->assertSame(0, DB::table('registro_fichas')->whereNotNull('error')->count());

        // Otra noche: la ficha recién enviada no se vuelve a leer ni a subir
        $enviados = [];
        $this->artisan('registro:importar')->assertSuccessful();
        $this->assertSame([], $enviados);
        $this->assertSame(2, $subidas);
    }

    public function test_sin_subir_fotos_envia_sin_fotos_y_luego_reencola(): void
    {
        config(['registro.pausa_ms' => 0, 'registro.geocodificar' => false, 'registro.alojarse.secreto' => 'sek', 'registro.tipos' => ['1' => 'Alojamiento'], 'registro.fotos.subir' => false]);
        $enviados = [];
        Http::fake(function (Request $r) use (&$enviados) {
            $u = $r->url();
            if (str_contains($u, 'alojarse.cl')) {
                $this->assertNotSame('subir_foto', $r['accion'], 'no debe subir fotos');
                $enviados = array_merge($enviados, $r['items']);

                return Http::response(['resultados' => array_map(fn ($i) => ['id' => $i['id'], 'estado' => 'creado', 'aviso_id' => 'c' . $i['id'], 'entidad' => 'Cabin'], $r['items'])]);
            }
            if (str_contains($u, 'resultado.php')) {
                return Http::response(str_contains($u, 'page=1') ? $this->html('listado.html') : '<html>0 Servicios</html>');
            }
            if (str_contains($u, '/fotos/')) {
                $this->fail('no debe descargar fotos');
            }
            if (str_contains($u, '25890')) {
                return Http::response('no', 404);
            }

            return Http::response($this->html('ficha.html'));
        });

        $this->artisan('registro:importar')->assertSuccessful();
        $this->assertCount(1, $enviados);
        $this->assertSame([], $enviados[0]['fotos']);
        $this->assertFalse($enviados[0]['fotos_completas'], 'incompletas: alojarse no borra fotos que ya tenga');

        $this->artisan('registro:importar', ['--reencolar-fotos' => true])->assertSuccessful();
        $this->assertSame('pendiente', DB::table('registro_fichas')->where('registro_id', '31002')->value('estado'));
    }

    public function test_sin_secreto_no_envia(): void
    {
        config(['registro.alojarse.secreto' => null]);
        $this->expectException(\RuntimeException::class);
        $this->artisan('registro:importar', ['--probar' => true]);
    }
}
