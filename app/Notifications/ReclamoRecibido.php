<?php

namespace App\Notifications;

use App\Models\Reclamo;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Al equipo: alguien pidió tomar un aviso. */
class ReclamoRecibido extends Notification
{
    public function __construct(public Reclamo $reclamo) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $r = $this->reclamo->loadMissing('aviso.localidad', 'usuario');

        return (new MailMessage)
            ->subject('Nueva solicitud: '.$r->aviso->titulo)
            ->greeting('Nueva solicitud “¿Es tu negocio?”')
            ->line('**'.$r->aviso->titulo.'** · '.$r->aviso->localidad->nombre)
            ->line('Solicitante: '.$r->usuario->name.' ('.$r->usuario->email.') · tel. '.$r->telefono)
            ->when($r->mensaje, fn ($m) => $m->line('Mensaje: '.$r->mensaje))
            ->action('Revisar en el panel', url('/admin/reclamos'))
            ->salutation(' ');
    }
}
