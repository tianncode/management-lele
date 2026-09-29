<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'transaction_date' =>
            $this->transaction_date?->format('Y-m-d'),

            'type' => $this->type,

            'category' => $this->category,

            'reference_type' => $this->reference_type,

            'reference_id' => $this->reference_id,

            'description' => $this->description,

            'amount' => (float) $this->amount,

            'created_at' =>
            $this->created_at?->toISOString(),

            'updated_at' =>
            $this->updated_at?->toISOString(),
        ];
    }
}
