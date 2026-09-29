<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HarvestResource extends JsonResource
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

            'harvest_date' => $this->harvest_date?->format('Y-m-d'),

            'total_fish' => (int) $this->total_fish,

            'total_weight' => (float) $this->total_weight,

            'average_weight' => (float) $this->average_weight,

            'selling_price_per_kg' => (float) $this->selling_price_per_kg,

            'estimated_revenue' => (float) $this->estimated_revenue,

            'notes' => $this->notes,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
