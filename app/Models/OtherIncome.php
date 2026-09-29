<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtherIncome extends Model
{
    protected $fillable = [
        'income_date',
        'category',
        'description',
        'amount',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'income_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
