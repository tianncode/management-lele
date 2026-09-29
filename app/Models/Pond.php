<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pond extends Model
{
    protected $fillable = [
        'code',
        'name',
        'length',
        'width',
        'depth',
        'volume',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'depth' => 'decimal:2',
            'volume' => 'decimal:3',
        ];
    }

    public function fishCycles(): HasMany
    {
        return $this->hasMany(FishCycle::class);
    }
}
