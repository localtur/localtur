<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Datos que nunca se muestran en el sitio público. */
class AvisoPrivado extends Model
{
    protected $table = 'avisos_privados';

    protected $fillable = [
        'aviso_id', 'id_semilla', 'fuente', 'nombre_original', 'clase_original', 'razon_social', 'rut',
        'telefono_original', 'telefono', 'estado_telefono', 'correo', 'web', 'no_contactar', 'notas',
    ];

    protected $casts = ['no_contactar' => 'boolean'];

    // Sin $hidden a propósito: el panel lo usa para llenar el formulario. Este modelo nunca se pasa a vistas públicas
    // ni a respuestas JSON; la prueba SitioTest verifica que nada de esto aparezca en las páginas.

    public function aviso(): BelongsTo
    {
        return $this->belongsTo(Aviso::class);
    }
}
