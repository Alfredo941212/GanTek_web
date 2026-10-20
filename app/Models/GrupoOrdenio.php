<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoOrdenio extends Model
{
    use HasFactory;

    protected $table = 'grupos_ordenio';

    protected $fillable = [
        'user_id',
        'nombre',
        'orden',
        'estado',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ganado()
    {
        return $this->belongsToMany(
            Ganado::class,
            'grupo_ordenio_ganado',
            'grupo_ordenio_id',
            'ganado_id'
        )->withTimestamps();
    }

    public function sesiones()
    {
        return $this->hasMany(
            SesionOrdenio::class,
            'grupo_ordenio_id'
        );
    }
}