<?php

namespace App\Models;

use App\Support\Marca;
use App\Support\Pais as PaisSitio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Aviso extends Model
{
    protected $table = 'avisos';

    public const ESTADOS = [
        'sin_activar' => 'Sin activar',
        'activo_gratis' => 'Activación gratis',
        'completo' => 'Activación completa',
        'anual' => 'Plan anual',
        'basico' => 'Aviso básico',
        'pausado' => 'Pausado',
    ];

    protected $fillable = [
        'localidad_id', 'user_id', 'categoria', 'subtipo', 'titulo', 'slug', 'descripcion_corta', 'descripcion',
        'direccion', 'lat', 'lng', 'ubicacion_aproximada', 'estado', 'tramo', 'plan_vence', 'origen',
        'imagen_referencial', 'fotos', 'indexable',
    ];

    protected $casts = [
        'fotos' => 'array',
        'ubicacion_aproximada' => 'boolean',
        'indexable' => 'boolean',
        'plan_vence' => 'date',
    ];

    public function localidad(): BelongsTo
    {
        return $this->belongsTo(Localidad::class);
    }

    public function dueno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function privado(): HasOne
    {
        return $this->hasOne(AvisoPrivado::class);
    }

    /** Avisos visibles en el sitio público (todo menos pausados). */
    public function scopePublicados(Builder $q): Builder
    {
        return $q->where('estado', '!=', 'pausado');
    }

    public function getCategoriaInfoAttribute(): array
    {
        return Marca::categoria($this->categoria);
    }

    public function getActivadoAttribute(): bool
    {
        return in_array($this->estado, ['activo_gratis', 'completo', 'anual'], true);
    }

    /** alojarse.cl/alojamiento/puerto-varas/x  ·  localtur.com/cl/alojamiento/puerto-varas/x */
    public function getUrlAttribute(): string
    {
        $region = $this->localidad->comuna->region;
        $pais = strtolower($region->pais->codigo ?? 'cl');

        return PaisSitio::rutaParaRegion('aviso.ver', [$this->categoria_info['slug'], $this->localidad->slug, $this->slug], $pais, $region->slug);
    }
}
