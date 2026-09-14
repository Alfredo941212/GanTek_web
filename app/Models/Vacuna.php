<?php

namespace App\Models;

use Database\Factories\VacunaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vacuna extends Model
{
    /** @use HasFactory<VacunaFactory> */
    use HasFactory;

    protected $table = 'vacunas';

    /** @var list<string> */
    protected $fillable = ['nombre', 'fabricante', 'descripcion', 'dosis_recomendada', 'intervalo_dias', 'estado'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['intervalo_dias' => 'integer'];
    }

    public function vacunaciones(): HasMany
    {
        return $this->hasMany(Vacunacion::class, 'vacuna_id');
    }
}
