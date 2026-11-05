<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pais extends Model
{
    protected $table = 'paises';

    protected $fillable = ['codigo', 'nombre', 'moneda'];

    public function regiones(): HasMany
    {
        return $this->hasMany(Region::class);
    }
}
