<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PondResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,

            'dimensions' => [
                'length' => (float) $this->length,
                'width' => (float) $this->width,
                'depth' => (float) $this->depth,
            ],

            'volume' => (float) $this->volume,

            'status' => $this->status,

            'notes' => $this->notes,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
