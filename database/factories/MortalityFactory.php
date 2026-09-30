<?php

namespace Database\Factories;

use App\Models\FishCycle;
use App\Models\Mortality;
use Illuminate\Database\Eloquent\Factories\Factory;

class MortalityFactory extends Factory
{
    protected $model = Mortality::class;

    public function definition(): array
    {
        return [
            'fish_cycle_id' => FishCycle::factory(),
            'mortality_date' => fake()->date(),
            'quantity' => fake()->numberBetween(1, 50),
            'cause' => fake()->randomElement([
                'Penyakit',
                'Kualitas air',
                'Stres',
                'Lainnya',
            ]),
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }
}
