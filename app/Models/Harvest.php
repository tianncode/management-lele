<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Harvest extends Model
{
    protected $fillable = [
        'fish_cycle_id',
        'harvest_date',
        'total_fish',
        'total_weight',
        'average_weight',
        'selling_price_per_kg',
        'estimated_revenue',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'harvest_date' => 'date',
            'total_fish' => 'integer',
            'total_weight' => 'decimal:2',
            'average_weight' => 'decimal:2',
            'selling_price_per_kg' => 'decimal:2',
            'estimated_revenue' => 'decimal:2',
        ];
    }
    public function fishCycle(): BelongsTo
    {
        return $this->belongsTo(FishCycle::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
