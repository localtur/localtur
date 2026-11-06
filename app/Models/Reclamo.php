<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Reclamo extends Model
{
    public const RELACIONES = ['dueno' => 'Soy el dueño', 'administrador' => 'Lo administro', 'encargado' => 'Soy encargado o trabajo ahí'];

    public const ESTADOS = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];

    protected $fillable = ['aviso_id', 'user_id', 'relacion', 'telefono', 'mensaje', 'estado', 'revisado_por', 'revisado_en', 'motivo_rechazo'];

    protected $casts = ['revisado_en' => 'datetime'];

    public function aviso(): BelongsTo
    {
        return $this->belongsTo(Aviso::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Aprobar: el aviso pasa a su dueño y queda con la activación gratis. Los demás reclamos del aviso se rechazan. */
    public function aprobar(User $revisor): void
    {
        DB::transaction(function () use ($revisor) {
            $this->update(['estado' => 'aprobado', 'revisado_por' => $revisor->id, 'revisado_en' => now()]);
            $this->aviso->update([
                'user_id' => $this->user_id,
                'estado' => $this->aviso->estado === 'sin_activar' ? 'activo_gratis' : $this->aviso->estado,
            ]);
            if ($this->usuario->rol === 'viajero') {
                $this->usuario->forceFill(['rol' => 'anfitrion'])->save();
            }
            static::where('aviso_id', $this->aviso_id)->where('id', '!=', $this->id)->where('estado', 'pendiente')
                ->update(['estado' => 'rechazado', 'motivo_rechazo' => 'Otra persona acreditó ser responsable del aviso', 'revisado_por' => $revisor->id, 'revisado_en' => now()]);
        });
        $this->avisar();
    }

    /** Correo al solicitante. Si el correo falla, la aprobación igual queda hecha. */
    public function avisar(): void
    {
        try {
            $this->usuario->notify(new \App\Notifications\ReclamoResuelto($this));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Correo al equipo cuando llega una solicitud nueva. */
    public function avisarAlEquipo(): void
    {
        if (! ($correo = config('mail.equipo'))) {
            return;
        }
        try {
            \Illuminate\Support\Facades\Notification::route('mail', $correo)->notify(new \App\Notifications\ReclamoRecibido($this));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function rechazar(User $revisor, ?string $motivo = null): void
    {
        $this->update(['estado' => 'rechazado', 'motivo_rechazo' => $motivo, 'revisado_por' => $revisor->id, 'revisado_en' => now()]);
        $this->avisar();
    }
}
