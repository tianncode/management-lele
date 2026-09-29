<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'category' => $this->whenLoaded(
                'category',
                fn() => [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ]
            ),

            'code' => $this->code,
            'name' => $this->name,
            'item_type' => $this->item_type,
            'unit' => $this->unit,

            'purchase_price' => (float) $this->purchase_price,
            'selling_price' => (float) $this->selling_price,

            'stock' => [
                'current' => (float) $this->current_stock,
                'minimum' => (float) $this->minimum_stock,
                'is_low' => $this->current_stock <= $this->minimum_stock,
            ],

            'is_active' => (bool) $this->is_active,
            'description' => $this->description,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
