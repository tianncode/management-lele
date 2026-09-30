<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FishCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'pond_id',
        'seed_product_id',
        'code',
        'start_date',
        'target_harvest_date',
        'initial_fish_count',
        'seed_size',
        'seed_unit_price',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'target_harvest_date' => 'date',
            'seed_size' => 'decimal:2',
            'seed_unit_price' => 'decimal:2',
        ];
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function seedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'seed_product_id');
    }

    public function feedings(): HasMany
    {
        return $this->hasMany(Feeding::class);
    }

    public function mortalities(): HasMany
    {
        return $this->hasMany(Mortality::class);
    }

    public function samplings(): HasMany
    {
        return $this->hasMany(Sampling::class);
    }

    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class);
    }
}
