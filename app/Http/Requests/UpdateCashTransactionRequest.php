<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCashTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_date' => [
                'required',
                'date',
            ],

            'type' => [
                'required',
                Rule::in([
                    'in',
                    'out',
                ]),
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
        ];
    }
}
