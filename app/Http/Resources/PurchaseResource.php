<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'supplier' => $this->whenLoaded(
                'supplier',
                fn() => [
                    'id' => $this->supplier->id,
                    'name' => $this->supplier->name,
                ]
            ),

            'invoice_number' => $this->invoice_number,

            'purchase_date' =>
            $this->purchase_date?->format('Y-m-d'),

            'subtotal' => (float) $this->subtotal,

            'discount' => (float) $this->discount,

            'additional_cost' =>
            (float) $this->additional_cost,

            'total' => (float) $this->total,

            'payment_status' => $this->payment_status,

            'notes' => $this->notes,

            'items' => PurchaseItemResource::collection(
                $this->whenLoaded('items')
            ),

            'created_at' =>
            $this->created_at?->toISOString(),

            'updated_at' =>
            $this->updated_at?->toISOString(),
        ];
    }
}
