<?php

namespace Database\Factories;

use App\Models\FishCycle;
use App\Models\Pond;
use Illuminate\Database\Eloquent\Factories\Factory;

class FishCycleFactory extends Factory
{
    protected $model = FishCycle::class;

    public function definition(): array
    {
        return [
            'pond_id' => Pond::factory(),
            'seed_product_id' => null,
            'code' => 'CYCLE-' . fake()->unique()->numerify('#####'),
            'start_date' => now()->subDays(30)->toDateString(),
            'target_harvest_date' => now()->addDays(30)->toDateString(),
            'initial_fish_count' => 1000,
            'seed_size' => 5.00,
            'seed_unit_price' => 1000.00,
            'status' => 'active',
            'notes' => null,
        ];
    }
}
