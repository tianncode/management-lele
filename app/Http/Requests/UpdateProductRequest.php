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
                'max:255',
            ],

            'unit' => [
                'required',
                'string',
                'max:30',
            ],

            'current_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'minimum_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'average_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'is_active' => [
                'boolean',
            ],
        ];
    }
}
