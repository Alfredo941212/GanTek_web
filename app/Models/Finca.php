<?php

namespace App\Models;

use Database\Factories\FincaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Finca extends Model
{
    /** @use HasFactory<FincaFactory> */
    use HasFactory;

    protected $table = 'fincas';

    /** @var list<string> */
    protected $fillable = ['nombre', 'municipio', 'localidad', 'estado', 'estado_id', 'municipio_id', 'localidad_id', 'superficie', 'observaciones'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['superficie' => 'decimal:2'];
    }

    /** @param Builder<Finca> $query
     * @return Builder<Finca>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return array{estado: ?string, municipio: ?string, localidad: ?string} */
    public function clavesUbicacion(): array
    {
        return [
            'estado' => DB::table('estados')->where('id', $this->estado_id)->value('clave_inegi'),
            'municipio' => DB::table('municipios')->where('id', $this->municipio_id)->value('clave_inegi'),
            'localidad' => DB::table('localidades')->where('id', $this->localidad_id)->value('clave_inegi'),
        ];
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class, 'finca_id');
    }
}
