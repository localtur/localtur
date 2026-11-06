<?php

namespace Tests\Feature;

use App\Models\Aviso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitioTest extends TestCase
{
    use RefreshDatabase;

    private function importar(array $extra = []): void
    {
        $this->artisan('localtur:importar-semilla', [
            'publico' => base_path('tests/fixtures/semilla_publico.csv'),
            'privado' => base_path('tests/fixtures/semilla_privado.csv'),
        ] + $extra)->assertSuccessful();
    }

    public function test_importa_la_semilla_y_omite_filas_invalidas(): void
    {
        $this->importar();
        $this->assertSame(6, Aviso::count());
        $a = Aviso::where('titulo', 'Cabañas Lago Prueba')->firstOrFail();
        $this->assertSame('sin_activar', $a->estado);
        $this->assertSame('alojar', $a->categoria);
        $this->assertSame('11.111.111-1', $a->privado->rut);
        $this->assertSame('Lagos Llanquihue y Todos Los Santos', $a->localidad->destino);
    }

    public function test_reimportar_no_duplica_ni_pisa_avisos_activados(): void
    {
        $this->importar();
        Aviso::where('titulo', 'Hostal de Ejemplo')->update(['estado' => 'completo', 'titulo' => 'Hostal Renovado']);
        $this->importar();
        $this->assertSame(6, Aviso::count());
        $this->assertTrue(Aviso::where('titulo', 'Hostal Renovado')->exists());
    }

    public function test_filtro_por_comuna(): void
    {
        $this->importar(['--comuna' => 'Puerto Varas']);
        $this->assertSame(4, Aviso::count());
    }

    public function test_alojarse_muestra_regiones_menos_la_metropolitana(): void
    {
        $this->importar();
        $this->get('http://alojarse.cl/')->assertOk()->assertSee('Puerto Varas')->assertDontSee('Santiago Centro')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->get('http://alojarse.cl/alojamiento/puerto-varas')->assertOk()->assertSee('Cabañas Lago Prueba')->assertDontSee('Kayak Inventado');

        $r = $this->get('http://alojarse.cl/alojamiento/puerto-varas/cabanas-lago-prueba');
        $r->assertOk()->assertSee('Cabañas Lago Prueba')->assertSee('application/ld+json', false)
            ->assertSee('aún no ha sido activado')->assertSee('Aquí podrían ir tus fotos', false)
            ->assertSee('<link rel="canonical" href="http://alojarse.cl/alojamiento/puerto-varas/cabanas-lago-prueba">', false);
        // Los datos privados nunca salen en la página pública
        $r->assertDontSee('11.111.111-1')->assertDontSee('+56652999999')->assertDontSee('Sociedad Inventada');

        $this->get('http://alojarse.cl/que-hacer/puerto-varas/kayak-inventado')->assertOk();
        $this->get('http://alojarse.cl/alojamiento/puerto-varas/no-existe')->assertNotFound();
        $this->get('http://alojarse.cl/categoria-falsa/puerto-varas')->assertNotFound();
        // La Región Metropolitana no está en alojarse
        $this->get('http://alojarse.cl/alojamiento/santiago')->assertNotFound();
        $this->get('http://www.alojarse.cl/alojamiento/puerto-varas')->assertRedirect('https://alojarse.cl/alojamiento/puerto-varas');
    }

    public function test_localtur_por_pais_y_solo_metropolitana_en_chile(): void
    {
        $this->importar();
        $this->get('/')->assertOk()->assertSee('Chile')->assertSee('Perú')->assertSee('Colombia')->assertSee('México');

        $this->get('/cl')->assertOk()->assertSee('Santiago')->assertDontSee('Puerto Varas')
            ->assertSee('alojarse.cl'); // enlace al resto de Chile
        $this->get('/cl/alojamiento/santiago')->assertOk()->assertSee('Hostal Santiago Centro Prueba');
        $this->get('/cl/alojamiento/santiago/hostal-santiago-centro-prueba')->assertOk();
        $this->get('/cl/alojamiento/puerto-varas')->assertNotFound();

        $this->get('/pe')->assertOk()->assertSee('Estamos llegando a Perú')->assertSee('Cusco');
        $this->get('/co')->assertOk()->assertSee('Estamos llegando a Colombia')->assertSee('Cartagena');
        $this->get('/mx')->assertOk()->assertSee('Estamos llegando a México')->assertSee('Oaxaca');
        $this->get('/ar')->assertNotFound();
    }

    public function test_robots_bloquea_todo_en_prototipo(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
    }

    public function test_panel_solo_para_admin(): void
    {
        $this->importar();
        $viajero = User::factory()->create();
        $this->actingAs($viajero)->get('/admin/avisos')->assertForbidden();

        $admin = User::factory()->create();
        $admin->forceFill(['rol' => 'admin'])->save();
        $this->actingAs($admin)->get('/admin/avisos')->assertOk()->assertSee('Cabañas Lago Prueba');
        $id = Aviso::where('titulo', 'Cabañas Lago Prueba')->value('id');
        $this->actingAs($admin)->get("/admin/avisos/$id/edit")->assertOk()->assertSee('11.111.111-1');
        $this->actingAs($admin)->get('/admin/avisos?tableFilters[estado][value]=sin_activar')->assertOk();
    }
}
