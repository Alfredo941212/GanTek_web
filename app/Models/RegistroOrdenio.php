<?php

namespace App\Models;

use Database\Factories\RegistroOrdenioFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;

class RegistroOrdenio extends Model
{
    /** @use HasFactory<RegistroOrdenioFactory> */
    use HasFactory;

    protected $table = 'registros_ordenio';

    /** @var list<string> */
    protected $fillable = ['ganado_id', 'lote_historico_id', 'fecha', 'turno', 'litros', 'observaciones'];

    public function setFechaAttribute(\DateTimeInterface|string|null $value): void
    {
        $this->attributes['fecha'] = $value === null ? null : Date::parse($value)->toDateString();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['fecha' => 'date', 'litros' => 'decimal:2'];
    }

    /** @param Builder<RegistroOrdenio> $query
     * @return Builder<RegistroOrdenio>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('ganado', fn (Builder $ganado) => $ganado->forUser($user))->whereHas('loteHistorico', fn (Builder $lotes) => $lotes->forUser($user));
    }

    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class, 'ganado_id');
    }

    public function loteHistorico(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_historico_id');
    }
}
