<?php

namespace App\Notifications;

use App\Models\Reclamo;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Al solicitante: su solicitud fue aprobada o rechazada. */
class ReclamoResuelto extends Notification
{
    public function __construct(public Reclamo $reclamo) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $r = $this->reclamo->loadMissing('aviso');
        $m = (new MailMessage)->greeting('Hola, '.strtok($notifiable->name, ' '));

        if ($r->estado === 'aprobado') {
            return $m->subject('Tu aviso “'.$r->aviso->titulo.'” ya es tuyo')
                ->line('Aprobamos tu solicitud. Ya puedes administrar **'.$r->aviso->titulo.'**: sube hasta 10 fotos, escribe la descripción y agrega tu contacto directo.')
                ->action('Editar mi aviso', route('cuenta.aviso.editar', $r->aviso))
                ->line('La activación es gratis. El trato con tus clientes es directo: no cobramos comisiones.')
                ->salutation('Saludos, el equipo de '.config('app.name'));
        }

        return $m->subject('Sobre tu solicitud para “'.$r->aviso->titulo.'”')
            ->line('No pudimos aprobar tu solicitud para **'.$r->aviso->titulo.'**.')
            ->when($r->motivo_rechazo, fn ($x) => $x->line('Motivo: '.$r->motivo_rechazo))
            ->line('Si crees que es un error, responde a este correo o vuelve a intentarlo con más datos para verificar.')
            ->salutation('Saludos, el equipo de '.config('app.name'));
    }
}
