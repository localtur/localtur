<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ProbarCorreo extends Command
{
    protected $signature = 'localtur:probar-correo {email}';

    protected $description = 'Envía un correo de prueba para revisar la configuración SMTP';

    public function handle(): int
    {
        try {
            Mail::raw('Si lees esto, el correo de '.config('app.name').' funciona. Enviado desde '.config('mail.from.address').'.', function ($m) {
                $m->to($this->argument('email'))->subject('Prueba de correo · '.config('app.name'));
            });
        } catch (\Throwable $e) {
            $this->error('No se pudo enviar: '.$e->getMessage());

            return self::FAILURE;
        }
        $this->info('Enviado a '.$this->argument('email').'. Revisa también la carpeta de spam.');

        return self::SUCCESS;
    }
}
