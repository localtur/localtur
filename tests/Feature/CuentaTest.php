<?php

namespace Tests\Feature;

use App\Models\Aviso;
use App\Models\Reclamo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CuentaTest extends TestCase
{
    use RefreshDatabase;

    private function importar(): void
    {
        $this->artisan('localtur:importar-semilla', [
            'publico' => base_path('tests/fixtures/semilla_publico.csv'),
            'privado' => base_path('tests/fixtures/semilla_privado.csv'),
        ])->assertSuccessful();
    }

    private function admin(): User
    {
        $a = User::factory()->create();
        $a->forceFill(['rol' => 'admin'])->save();

        return $a;
    }

    public function test_registro_e_ingreso(): void
    {
        $this->get('/registro')->assertOk()->assertSee('Crea tu cuenta');
        $this->post('/registro', [
            'name' => 'Ana Pérez', 'email' => 'Ana@Ejemplo.cl', 'password' => 'clave-segura-123',
            'password_confirmation' => 'clave-segura-123', 'acepto' => '1',
        ])->assertRedirect('/cuenta');
        $this->assertAuthenticated();
        $this->assertSame('viajero', User::firstWhere('email', 'ana@ejemplo.cl')->rol);

        $this->post('/cuenta/salir')->assertRedirect();
        $this->assertGuest();

        $this->post('/ingresar', ['email' => 'ana@ejemplo.cl', 'password' => 'mala'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/ingresar', ['email' => 'ana@ejemplo.cl', 'password' => 'clave-segura-123'])->assertRedirect('/cuenta');
        $this->assertAuthenticated();
        $this->get('/cuenta')->assertOk()->assertSee('Hola, Ana');
    }

    public function test_registro_exige_aceptar_terminos(): void
    {
        $this->post('/registro', [
            'name' => 'X', 'email' => 'x@ejemplo.cl', 'password' => 'clave-segura-123', 'password_confirmation' => 'clave-segura-123',
        ])->assertSessionHasErrors('acepto');
        $this->assertGuest();
    }

    public function test_reclamar_aprobar_editar_y_contacto_publico(): void
    {
        Storage::fake('public');
        $this->importar();
        $aviso = Aviso::where('titulo', 'Cabañas Lago Prueba')->firstOrFail();

        // Sin sesión: lleva a ingresar
        $this->get("/cuenta/reclamar/{$aviso->id}")->assertRedirect('/ingresar');

        $dueno = User::factory()->create(['name' => 'Pedro Dueño']);
        $this->actingAs($dueno)->get("/cuenta/reclamar/{$aviso->id}")->assertOk()->assertSee('¿Es tu negocio?');
        $this->actingAs($dueno)->post("/cuenta/reclamar/{$aviso->id}", ['relacion' => 'dueno', 'telefono' => '+56 9 1234 5678'])
            ->assertSessionHasErrors('acepto');
        $this->actingAs($dueno)->post("/cuenta/reclamar/{$aviso->id}", ['relacion' => 'dueno', 'telefono' => '+56 9 1234 5678', 'acepto' => '1'])
            ->assertRedirect('/cuenta');
        $reclamo = Reclamo::firstOrFail();
        $this->assertSame('pendiente', $reclamo->estado);

        // Antes de aprobar no puede editar
        $this->actingAs($dueno)->get("/cuenta/avisos/{$aviso->id}/editar")->assertForbidden();

        // El equipo lo ve en el panel y aprueba
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/reclamos')->assertOk()->assertSee('Cabañas Lago Prueba');
        $reclamo->aprobar($admin);
        $aviso->refresh();
        $this->assertSame($dueno->id, $aviso->user_id);
        $this->assertSame('activo_gratis', $aviso->estado);
        $this->assertSame('anfitrion', $dueno->fresh()->rol);

        // Edita: descripción, contacto y 2 fotos
        $this->actingAs($dueno)->get("/cuenta/avisos/{$aviso->id}/editar")->assertOk();
        $this->actingAs($dueno)->put("/cuenta/avisos/{$aviso->id}", [
            'titulo' => 'Cabañas Lago Prueba',
            'descripcion' => 'Cabañas con vista al lago y tinaja.',
            'contacto_whatsapp' => '+56 9 1234 5678',
            'contacto_web' => 'https://ejemplo.cl',
            'fotos_nuevas' => [UploadedFile::fake()->image('a.jpg', 2400, 1600), UploadedFile::fake()->image('b.png', 800, 600)],
        ])->assertRedirect("/cuenta/avisos/{$aviso->id}/editar");
        $aviso->refresh();
        $this->assertCount(2, $aviso->fotos);
        Storage::disk('public')->assertExists($aviso->fotos[0]);
        [$w] = getimagesizefromstring(Storage::disk('public')->get($aviso->fotos[0]));
        $this->assertLessThanOrEqual(1600, $w);

        // Página pública (alojarse, porque Puerto Varas no es Región Metropolitana)
        $this->get('http://alojarse.cl/alojamiento/puerto-varas/cabanas-lago-prueba')->assertOk()
            ->assertSee('Contacto directo')->assertSee('https://wa.me/56912345678', false)
            ->assertSee('Cabañas con vista al lago')->assertDontSee('¿Es tu negocio?')
            ->assertDontSee('11.111.111-1');

        // Quitar una foto y no pasar de 10
        $this->actingAs($dueno)->put("/cuenta/avisos/{$aviso->id}", ['titulo' => 'Cabañas Lago Prueba', 'borrar' => [$aviso->fotos[1]]]);
        $this->assertCount(1, $aviso->fresh()->fotos);
        $once = array_map(fn ($i) => UploadedFile::fake()->image("f$i.jpg", 100, 100), range(1, 10));
        $this->actingAs($dueno)->put("/cuenta/avisos/{$aviso->id}", ['titulo' => 'Cabañas Lago Prueba', 'fotos_nuevas' => $once])
            ->assertSessionHasErrors('fotos_nuevas');

        // Otra persona no puede editarlo
        $otro = User::factory()->create();
        $this->actingAs($otro)->get("/cuenta/avisos/{$aviso->id}/editar")->assertForbidden();
    }

    public function test_publicar_negocio_nuevo_en_mexico(): void
    {
        $u = User::factory()->create();
        $this->withSession(['pais' => 'mx']); // el último país visitado queda en la sesión
        $this->actingAs($u)->get('/cuenta/avisos/nuevo')->assertOk()->assertSee('Publica tu negocio en México')->assertSee('Estado');
        $this->actingAs($u)->post('/cuenta/avisos', [
            'categoria' => 'alojar', 'subtipo' => 'Hostal', 'titulo' => 'Casa Azul Oaxaca',
            'region' => 'Oaxaca', 'comuna' => 'Oaxaca de Juárez', 'acepto' => '1',
        ])->assertRedirect();
        $a = Aviso::firstWhere('titulo', 'Casa Azul Oaxaca');
        $this->assertSame('activo_gratis', $a->estado);
        $this->assertSame('MX', $a->localidad->comuna->region->pais->codigo);
        $this->get('/mx/alojamiento/oaxaca-de-juarez/casa-azul-oaxaca')->assertOk()->assertSee('Casa Azul Oaxaca');
        $this->get('/mx')->assertOk()->assertSee('Oaxaca de Juárez');
    }
}
