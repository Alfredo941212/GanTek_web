<?php

namespace App\Models;

use Database\Factories\VeterinarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Veterinario extends Model
{
    /** @use HasFactory<VeterinarioFactory> */
    use HasFactory;

    protected $table = 'veterinarios';

    /** @var list<string> */
    protected $fillable = ['nombre', 'cedula_profesional', 'telefono', 'correo', 'especialidad', 'estado', 'observaciones'];

    public function vacunaciones(): HasMany
    {
        return $this->hasMany(Vacunacion::class, 'veterinario_id');
    }
}
