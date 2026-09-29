<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'harvest' => $this->whenLoaded(
                'harvest',
                fn() => [
                    'id' => $this->harvest->id,
                    'harvest_date' =>
                    $this->harvest->harvest_date
                        ?->format('Y-m-d'),
                ]
            ),

            'description' => $this->description,

            'quantity' => (float) $this->quantity,

            'unit_price' => (float) $this->unit_price,

            'subtotal' => (float) $this->subtotal,
        ];
    }
}
