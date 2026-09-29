<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHarvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fish_cycle_id' => [
                'required',
                'integer',
                'exists:fish_cycles,id',
            ],

            'harvest_date' => [
                'required',
                'date',
            ],

            'total_fish' => [
                'required',
                'integer',
                'min:1',
            ],

            'total_weight' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'selling_price_per_kg' => [
                'required',
                'numeric',
                'gte:0',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
