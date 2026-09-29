<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sampling extends Model
{
    protected $fillable = [
        'fish_cycle_id',
        'sampling_date',
        'sample_count',
        'average_weight',
        'average_length',
        'estimated_biomass',
        'estimated_population',
        'notes',
        'created_by',
    ];
    
    protected function casts(): array
    {
        return [
            'sampling_date' => 'date',
            'sample_count' => 'integer',
            'average_weight' => 'decimal:2',
            'average_length' => 'decimal:2',
            'estimated_biomass' => 'decimal:2',
            'estimated_population' => 'integer',
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
