<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mortality extends Model
{
    protected $fillable = [
        'fish_cycle_id',
        'mortality_date',
        'quantity',
        'cause',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'mortality_date' => 'date',
            'quantity' => 'integer',
        ];
    }

    public function fishCycle(): BelongsTo
    {
        return $this->belongsTo(FishCycle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
