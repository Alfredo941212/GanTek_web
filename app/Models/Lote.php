<?php

namespace App\Models;

use Database\Factories\LoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lote extends Model
{
    /** @use HasFactory<LoteFactory> */
    use HasFactory;

    protected $table = 'lotes';

    /** @var list<string> */
    protected $fillable = ['finca_id', 'nombre', 'descripcion', 'estado'];

    /** @param Builder<Lote> $query
     * @return Builder<Lote>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('finca', fn (Builder $fincas) => $fincas->forUser($user));
    }

    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class, 'finca_id');
    }

    public function ganado(): HasMany
    {
        return $this->hasMany(Ganado::class, 'lote_id');
    }

    public function registrosOrdenioHistoricos(): HasMany
    {
        return $this->hasMany(RegistroOrdenio::class, 'lote_historico_id');
    }
}
