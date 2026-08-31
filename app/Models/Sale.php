<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'cattle_id',
        'sale_date',
        'weight',
        'price_per_kg',
        'total_amount',
        'buyer',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'weight' => 'decimal:2',
        'price_per_kg' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function cattle()
    {
        return $this->belongsTo(Cattle::class);
    }
}
