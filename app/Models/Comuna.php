<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Comuna extends Model
{
    protected $table = 'comunas';

    protected $fillable = ['region_id', 'nombre', 'slug', 'poblacion'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function localidades(): HasMany
    {
        return $this->hasMany(Localidad::class);
    }

    public function avisos(): HasManyThrough
    {
        return $this->hasManyThrough(Aviso::class, Localidad::class);
    }
}
