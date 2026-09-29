<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMortalityRequest extends FormRequest
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

            'mortality_date' => [
                'required',
                'date',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'cause' => [
                'nullable',
                'string',
                'max:100',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
