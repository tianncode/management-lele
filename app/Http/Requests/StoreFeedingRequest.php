<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedingRequest extends FormRequest
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

            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'feeding_date' => [
                'required',
                'date',
            ],

            'feeding_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'unit_price' => [
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
