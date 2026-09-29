<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSamplingRequest extends FormRequest
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

            'sampling_date' => [
                'required',
                'date',
            ],

            'sample_count' => [
                'required',
                'integer',
                'min:1',
            ],

            'average_weight' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'average_length' => [
                'nullable',
                'numeric',
                'gt:0',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
