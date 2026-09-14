<?php

namespace App\Models;

use Database\Factories\GanadoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Date;

class Ganado extends Model
{
    /** @use HasFactory<GanadoFactory> */
    use HasFactory;

    protected $table = 'ganado';

    /** @var list<string> */
    protected $fillable = ['lote_id', 'arete_siniiga', 'nombre', 'sexo', 'raza', 'fecha_nacimiento', 'fecha_ingreso', 'peso_inicial', 'estado', 'observaciones'];

    public function setFechaNacimientoAttribute(\DateTimeInterface|string|null $value): void
    {
        $this->attributes['fecha_nacimiento'] = $value === null ? null : Date::parse($value)->toDateString();
    }

    public function setFechaIngresoAttribute(\DateTimeInterface|string|null $value): void
    {
        $this->attributes['fecha_ingreso'] = $value === null ? null : Date::parse($value)->toDateString();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['fecha_nacimiento' => 'date', 'fecha_ingreso' => 'date', 'peso_inicial' => 'decimal:2'];
    }

    /** @param Builder<Ganado> $query
     * @return Builder<Ganado>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('lote', fn (Builder $lotes) => $lotes->forUser($user));
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    public function vacunaciones(): HasMany
    {
        return $this->hasMany(Vacunacion::class, 'ganado_id');
    }

    public function registrosOrdenio(): HasMany
    {
        return $this->hasMany(RegistroOrdenio::class, 'ganado_id');
    }
}
