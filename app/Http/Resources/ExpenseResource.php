<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
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

            'expense_date' =>
            $this->expense_date?->format('Y-m-d'),

            'description' => $this->description,

            'amount' => (float) $this->amount,

            'notes' => $this->notes,

            'created_at' =>
            $this->created_at?->toISOString(),

            'updated_at' =>
            $this->updated_at?->toISOString(),
        ];
    }
}
