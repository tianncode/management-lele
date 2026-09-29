<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MortalityResource extends JsonResource
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

            'mortality_date' => $this->mortality_date?->format('Y-m-d'),

            'quantity' => (int) $this->quantity,

            'cause' => $this->cause,

            'notes' => $this->notes,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
