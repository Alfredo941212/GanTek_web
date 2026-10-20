<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SesionOrdenio extends Model
{
    use HasFactory;

    protected $table = 'sesiones_ordenio';

    protected $fillable = [
        'uuid',
        'user_id',
        'grupo_ordenio_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'duracion_minutos',
        'cantidad_vacas',
        'litros_totales',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'datetime',
        'hora_fin' => 'datetime',
        'duracion_minutos' => 'integer',
        'cantidad_vacas' => 'integer',
        'litros_totales' => 'decimal:2',
    ];

    public function usuario()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function grupo()
    {
        return $this->belongsTo(
            GrupoOrdenio::class,
            'grupo_ordenio_id'
        );
    }

    public function ganado()
    {
        return $this->belongsToMany(
            Ganado::class,
            'sesion_ordenio_ganado',
            'sesion_ordenio_id',
            'ganado_id'
        )->withTimestamps();
    }
}