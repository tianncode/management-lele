<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOtherIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'income_date' => [
                'required',
                'date',
            ],

            'category' => [
                'required',
                'string',
                'max:100',
            ],

            'description' => [
                'required',
                'string',
                'max:255',
            ],

            'amount' => [
                'required',
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
