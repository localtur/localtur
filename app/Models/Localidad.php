<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Localidad extends Model
{
    protected $table = 'localidades';

    protected $fillable = ['comuna_id', 'nombre', 'slug', 'lat', 'lng', 'destino'];

    public function comuna(): BelongsTo
    {
        return $this->belongsTo(Comuna::class);
    }

    public function avisos(): HasMany
    {
        return $this->hasMany(Aviso::class);
    }

    public function scopeDelPais(Builder $q, string $codigo): Builder
    {
        return $q->whereHas('comuna.region.pais', fn ($p) => $p->where('codigo', strtoupper($codigo)));
    }

    /** Localidades que muestra la marca activa en el país activo (respeta el reparto de regiones). */
    public function scopeVisibles(Builder $q): Builder
    {
        $codigo = \App\Support\Pais::codigo();
        $f = \App\Support\Marca::actual()['regiones'][$codigo] ?? [];
        $q->delPais($codigo);
        if (! empty($f['solo'])) {
            $q->whereHas('comuna.region', fn ($r) => $r->whereIn('slug', $f['solo']));
        }
        if (! empty($f['excluir'])) {
            $q->whereHas('comuna.region', fn ($r) => $r->whereNotIn('slug', $f['excluir']));
        }

        return $q;
    }
}
