<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'category_id' => [
                'required',
                'integer',
                'exists:product_categories,id',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'code')
                    ->ignore($productId),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'item_type' => [
                'required',
                'string',
                'max:50',
            ],

            'unit' => [
                'required',
                'string',
                'max:20',
            ],

            'purchase_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'selling_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'minimum_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'current_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'is_active' => [
                'boolean',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ];
    }
}
