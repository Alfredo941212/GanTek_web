<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vaccine extends Model
{
    use HasFactory;

    protected $fillable = [
        'cattle_id',
        'vaccine_name',
        'application_date',
        'next_date',
        'notes',
    ];

    protected $casts = [
        'application_date' => 'date',
        'next_date' => 'date',
    ];

    public function cattle()
    {
        return $this->belongsTo(Cattle::class);
    }
}
