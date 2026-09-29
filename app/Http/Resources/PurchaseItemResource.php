<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'product' => $this->whenLoaded(
                'product',
                fn() => [
                    'id' => $this->product->id,
                    'code' => $this->product->code,
                    'name' => $this->product->name,
                ]
            ),

            'quantity' => (float) $this->quantity,

            'unit_price' => (float) $this->unit_price,

            'subtotal' => (float) $this->subtotal,
        ];
    }
}
