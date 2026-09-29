<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'fish_cycle' => $this->whenLoaded(
                'fishCycle',
                fn() => [
                    'id' => $this->fishCycle->id,
                    'code' => $this->fishCycle->code,
                ]
            ),

            'product' => $this->whenLoaded(
                'product',
                fn() => [
                    'id' => $this->product->id,
                    'code' => $this->product->code,
                    'name' => $this->product->name,
                    'unit' => $this->product->unit,
                ]
            ),

            'feeding_date' => $this->feeding_date?->format('Y-m-d'),
            'feeding_time' => $this->feeding_time,

            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'total_cost' => (float) $this->total_cost,

            'method' => $this->method,
            'notes' => $this->notes,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
