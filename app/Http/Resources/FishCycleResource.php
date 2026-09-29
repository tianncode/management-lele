<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FishCycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,

            'pond' => $this->whenLoaded(
                'pond',
                fn() => [
                    'id' => $this->pond->id,
                    'code' => $this->pond->code,
                    'name' => $this->pond->name,
                ]
            ),

            'start_date' => $this->start_date,
            'target_harvest_date' => $this->target_harvest_date,

            'initial_fish_count' => (int) $this->initial_fish_count,

            'initial_average_weight' => $this->initial_average_weight !== null
                ? (float) $this->initial_average_weight
                : null,

            'target_average_weight' => $this->target_average_weight !== null
                ? (float) $this->target_average_weight
                : null,

            'status' => $this->status,

            'notes' => $this->notes,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
