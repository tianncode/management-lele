<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFishCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $cycleId = $this->route('fish_cycle')?->id;

        return [
            'pond_id' => [
                'required',
                'integer',
                'exists:ponds,id',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('fish_cycles', 'code')
                    ->ignore($cycleId),
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'target_harvest_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'initial_fish_count' => [
                'required',
                'integer',
                'min:1',
            ],

            'initial_average_weight' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'target_average_weight' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'status' => [
                'required',
                Rule::in([
                    'planned',
                    'active',
                    'harvest',
                    'completed',
                    'cancelled',
                ]),
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
