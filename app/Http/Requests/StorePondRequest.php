<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePondRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:ponds,code',
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'length' => [
                'required',
                'numeric',
                'min:0.1',
            ],
            'width' => [
                'required',
                'numeric',
                'min:0.1',
            ],
            'depth' => [
                'required',
                'numeric',
                'min:0.1',
            ],
            'volume' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'status' => [
                'required',
                Rule::in([
                    'available',
                    'preparation',
                    'cultivation',
                    'harvest',
                    'maintenance',
                ]),
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
