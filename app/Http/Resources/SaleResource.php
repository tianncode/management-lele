<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'customer' => $this->whenLoaded(
                'customer',
                fn() => [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                ]
            ),

            'invoice_number' => $this->invoice_number,

            'sale_date' =>
            $this->sale_date?->format('Y-m-d'),

            'subtotal' => (float) $this->subtotal,

            'discount' => (float) $this->discount,

            'total' => (float) $this->total,

            'payment_status' => $this->payment_status,

            'notes' => $this->notes,

            'items' => SaleItemResource::collection(
                $this->whenLoaded('items')
            ),

            'created_at' =>
            $this->created_at?->toISOString(),

            'updated_at' =>
            $this->updated_at?->toISOString(),
        ];
    }
}
