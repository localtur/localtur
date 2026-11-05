<?php

namespace App\Models;

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
}
