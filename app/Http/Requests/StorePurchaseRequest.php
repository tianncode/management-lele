<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                'integer',
                'exists:suppliers,id',
            ],

            'invoice_number' => [
                'required',
                'string',
                'max:100',
                'unique:purchases,invoice_number',
            ],

            'purchase_date' => [
                'required',
                'date',
            ],

            'discount' => [
                'nullable',
                'numeric',
                'gte:0',
            ],

            'additional_cost' => [
                'nullable',
                'numeric',
                'gte:0',
            ],

            'payment_status' => [
                'required',
                Rule::in([
                    'unpaid',
                    'partial',
                    'paid',
                ]),
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

            'items.*.product_id' => [
                'required',
                'integer',
                'exists:products,id',
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
