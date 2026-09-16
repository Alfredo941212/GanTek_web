<?php

namespace App\Models;

use Database\Factories\VacunacionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;

class Vacunacion extends Model
{
    /** @use HasFactory<VacunacionFactory> */
    use HasFactory;

    protected $table = 'vacunaciones';

    /** @var list<string> */
    protected $fillable = ['ganado_id', 'veterinario_id', 'vacuna_id', 'fecha_aplicacion', 'proxima_aplicacion', 'dosis', 'observaciones'];

    public function setFechaAplicacionAttribute(\DateTimeInterface|string|null $value): void
    {
        $this->attributes['fecha_aplicacion'] = $value === null ? null : Date::parse($value)->toDateString();
    }

    public function setProximaAplicacionAttribute(\DateTimeInterface|string|null $value): void
    {
        $this->attributes['proxima_aplicacion'] = $value === null ? null : Date::parse($value)->toDateString();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['fecha_aplicacion' => 'date', 'proxima_aplicacion' => 'date'];
    }

    /** @param Builder<Vacunacion> $query
     * @return Builder<Vacunacion>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('ganado', fn (Builder $ganado) => $ganado->forUser($user));
    }

    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class, 'ganado_id');
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(Veterinario::class, 'veterinario_id');
    }

    public function vacuna(): BelongsTo
    {
        return $this->belongsTo(Vacuna::class, 'vacuna_id');
    }
}
