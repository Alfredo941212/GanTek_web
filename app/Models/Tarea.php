<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tarea extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ganado_id',
        'lote_id',
        'titulo',
        'descripcion',
        'tipo',
        'fecha',
        'hora',
        'prioridad',
        'estado',
        'recordatorio',
        'creada_por_ia',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'recordatorio' => 'boolean',
            'creada_por_ia' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }
}