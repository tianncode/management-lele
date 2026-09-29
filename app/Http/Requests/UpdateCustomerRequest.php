<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('customers', 'code')
                    ->ignore($customerId),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'contact_person' => [
                'nullable',
                'string',
                'max:100',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
