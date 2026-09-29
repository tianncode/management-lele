<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OtherIncomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'income_date' =>
            $this->income_date?->format('Y-m-d'),

            'category' => $this->category,

            'description' => $this->description,

            'amount' => (float) $this->amount,

            'notes' => $this->notes,

            'created_at' =>
            $this->created_at?->toISOString(),

            'updated_at' =>
            $this->updated_at?->toISOString(),
        ];
    }
}
