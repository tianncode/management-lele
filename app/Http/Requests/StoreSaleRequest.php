<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'nullable',
                'integer',
                'exists:customers,id',
            ],

            'invoice_number' => [
                'required',
                'string',
                'max:100',
                'unique:sales,invoice_number',
            ],

            'sale_date' => [
                'required',
                'date',
            ],

            'discount' => [
                'nullable',
                'numeric',
                'gte:0',
            ],

            'paid_amount' => [
                'required',
                'numeric',
                'gte:0',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.harvest_id' => [
                'required',
                'integer',
                'exists:harvests,id',
            ],

            'items.*.description' => [
                'nullable',
                'string',
                'max:255',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'items.*.unit_price' => [
                'required',
                'numeric',
                'gte:0',
            ],
        ];
    }
}
