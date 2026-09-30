<?php

namespace Database\Factories;

use App\Models\FishCycle;
use App\Models\Harvest;
use Illuminate\Database\Eloquent\Factories\Factory;

class HarvestFactory extends Factory
{
    protected $model = Harvest::class;

    public function definition(): array
    {
        $totalFish = fake()->numberBetween(50, 200);
        $totalWeight = fake()->randomFloat(2, 10, 100);
        $sellingPrice = fake()->randomFloat(2, 20000, 40000);

        return [
            'fish_cycle_id' => FishCycle::factory(),
            'harvest_date' => fake()->date(),
            'total_fish' => $totalFish,
            'total_weight' => $totalWeight,
            'average_weight' => ($totalWeight * 1000) / $totalFish,
            'selling_price_per_kg' => $sellingPrice,
            'estimated_revenue' => $totalWeight * $sellingPrice,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }
}
