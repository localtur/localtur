<?php

namespace Tests\Feature;

use App\Models\Aviso;
use App\Models\Reclamo;
use App\Models\User;
use App\Notifications\ReclamoRecibido;
use App\Notifications\ReclamoResuelto;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class CorreoTest extends TestCase
{
    use RefreshDatabase;

    public function test_recuperar_contrasena(): void
    {
        Notification::fake();
        $u = User::factory()->create(['email' => 'ana@ejemplo.cl']);

        $this->get('/olvide-mi-clave')->assertOk()->assertSee('¿Olvidaste tu contraseña?');
        $this->post('/olvide-mi-clave', ['email' => 'ANA@ejemplo.cl'])->assertSessionHas('ok');
        Notification::assertSentTo($u, ResetPassword::class, function ($n) use ($u) {
            $mail = $n->toMail($u);

            return $mail->subject === 'Cambia tu contraseña' && str_contains($mail->actionUrl, '/nueva-clave/');
        });

        // Correo inexistente: mismo mensaje, nada enviado
        $this->post('/olvide-mi-clave', ['email' => 'nadie@ejemplo.cl'])->assertSessionHas('ok');
        Notification::assertSentTimes(ResetPassword::class, 1);

        $token = Password::createToken($u);
        $this->get('/nueva-clave/'.$token.'?email=ana@ejemplo.cl')->assertOk()->assertSee('Elige una contraseña nueva');
        $this->post('/nueva-clave', ['token' => $token, 'email' => 'ana@ejemplo.cl', 'password' => 'otra-clave-segura', 'password_confirmation' => 'otra-clave-segura'])
            ->assertRedirect('/ingresar');
        $this->assertTrue(Hash::check('otra-clave-segura', $u->fresh()->password));

        $this->post('/nueva-clave', ['token' => 'falso', 'email' => 'ana@ejemplo.cl', 'password' => 'otra-clave-segura2', 'password_confirmation' => 'otra-clave-segura2'])
            ->assertSessionHasErrors('email');
    }

    public function test_correos_de_solicitudes(): void
    {
        Notification::fake();
        config(['mail.equipo' => 'equipo@ejemplo.cl']);
        $this->artisan('localtur:importar-semilla', ['publico' => base_path('tests/fixtures/semilla_publico.csv')])->assertSuccessful();
        $aviso = Aviso::firstWhere('titulo', 'Cabañas Lago Prueba');
        $u = User::factory()->create();

        $this->actingAs($u)->post("/cuenta/reclamar/{$aviso->id}", ['relacion' => 'dueno', 'telefono' => '+56 9 1111 2222', 'acepto' => '1']);
        Notification::assertSentTo(new AnonymousNotifiable, ReclamoRecibido::class, fn ($n, $ch, $to) => $to->routes['mail'] === 'equipo@ejemplo.cl');

        $admin = User::factory()->create();
        Reclamo::first()->aprobar($admin);
        Notification::assertSentTo($u, ReclamoResuelto::class, fn ($n) => str_contains($n->toMail($u)->subject, 'ya es tuyo'));
    }
}
