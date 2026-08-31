<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cattle extends Model
{
    use HasFactory;

    protected $table = 'cattle';

    protected $fillable = [
        'code',
        'name',
        'sex',
        'breed',
        'entry_date',
        'initial_weight',
        'lot',
        'corral',
        'status',
        'observations',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'initial_weight' => 'decimal:2',
    ];

    public function vaccines()
    {
        return $this->hasMany(Vaccine::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
