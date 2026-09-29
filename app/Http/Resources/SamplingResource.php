<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SamplingResource extends JsonResource
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

            'sampling_date' => $this->sampling_date?->format('Y-m-d'),

            'sample_count' => (int) $this->sample_count,

            'average_weight' => (float) $this->average_weight,

            'average_length' => $this->average_length !== null
                ? (float) $this->average_length
                : null,

            'estimated_population' => (int) $this->estimated_population,

            'estimated_biomass' => (float) $this->estimated_biomass,

            'notes' => $this->notes,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
