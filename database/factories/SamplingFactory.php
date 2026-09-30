<?php

namespace Database\Factories;

use App\Models\FishCycle;
use App\Models\Sampling;
use Illuminate\Database\Eloquent\Factories\Factory;

class SamplingFactory extends Factory
{
    protected $model = Sampling::class;

    public function definition(): array
    {
        return [
            'fish_cycle_id' => FishCycle::factory(),
            'sampling_date' => fake()->date(),
            'sample_count' => fake()->numberBetween(10, 100),
            'average_weight' => fake()->randomFloat(2, 5, 100),
            'average_length' => fake()->randomFloat(2, 5, 30),
            'estimated_biomass' => 0,
            'estimated_population' => 0,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }
}
