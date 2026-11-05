<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class HacerAdmin extends Command
{
    protected $signature = 'localtur:admin {email} {--nombre=Administrador}';

    protected $description = 'Crea (o convierte) un usuario con acceso al panel /admin. Pide la contraseña en pantalla.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::firstWhere('email', $email);
        if (! $user) {
            $clave = $this->secret('Contraseña nueva (no se muestra)');
            if (strlen((string) $clave) < 10) {
                $this->error('Usa al menos 10 caracteres.');

                return self::FAILURE;
            }
            $user = User::create([
                'name' => $this->option('nombre'),
                'email' => $email,
                'password' => Hash::make($clave),
            ]);
        }
        $user->forceFill(['rol' => 'admin'])->save();
        $this->info("Listo: $email ya puede entrar a /admin");

        return self::SUCCESS;
    }
}
