<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Valores predeterminados del usuario.
     */
    protected $attributes = [
        'puede_gestionar_catalogos' => false,
    ];

    /**
     * Campos que pueden asignarse mediante Eloquent.
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'phone',
        'password',
        'google_id',
        'facebook_id',
        'avatar',
        'firebase_uid',
    ];

    /**
     * Campos ocultos al serializar el usuario.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversión de tipos.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'puede_gestionar_catalogos' => 'boolean',
        ];
    }

    /**
     * Relación del usuario con sus fincas.
     */
    public function fincas(): HasMany
    {
        return $this->hasMany(Finca::class);
    }
}
