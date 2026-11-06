<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'telefono',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Solo el equipo de alojarse/localtur entra al panel de administración. */
    public function avisos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Aviso::class);
    }

    public function reclamos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reclamo::class);
    }

    public function esEquipo(): bool
    {
        return in_array($this->rol, ['admin', 'moderador'], true);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->rol, ['admin', 'moderador'], true);
    }
}
