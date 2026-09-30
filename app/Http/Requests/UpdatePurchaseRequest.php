<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseRequest extends FormRequest
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
                Rule::unique('purchases', 'invoice_number')
                    ->ignore($this->route('purchase')),
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
