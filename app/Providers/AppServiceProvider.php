<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Correo de recuperación de contraseña, en español
        ResetPassword::toMailUsing(function ($user, string $token) {
            $url = url(route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false));

            return (new MailMessage)
                ->subject('Cambia tu contraseña')
                ->greeting('Hola, '.strtok((string) $user->name, ' '))
                ->line('Recibimos una solicitud para cambiar la contraseña de tu cuenta.')
                ->action('Elegir una contraseña nueva', $url)
                ->line('El enlace vence en '.config('auth.passwords.users.expire', 60).' minutos. Si no fuiste tú, ignora este correo: tu contraseña sigue igual.')
                ->salutation('Saludos, el equipo de '.config('app.name'));
        });
    }
}
