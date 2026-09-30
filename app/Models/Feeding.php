<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feeding extends Model
{
    use HasFactory;

    protected $fillable = [
        'fish_cycle_id',
        'product_id',
        'feeding_date',
        'feeding_time',
        'quantity',
        'unit_price',
        'total_cost',
        'method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'feeding_date' => 'date',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }

    public function fishCycle(): BelongsTo
    {
        return $this->belongsTo(FishCycle::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
