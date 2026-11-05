<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $table = 'regiones';

    protected $fillable = ['pais_id', 'nombre', 'slug', 'orden'];

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class);
    }

    public function comunas(): HasMany
    {
        return $this->hasMany(Comuna::class);
    }
}
