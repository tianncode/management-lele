<?php

namespace Database\Factories;

use App\Models\Feeding;
use App\Models\FishCycle;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedingFactory extends Factory
{
    protected $model = Feeding::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 10);
        $unitPrice = fake()->randomFloat(2, 5000, 50000);

        return [
            'fish_cycle_id' => FishCycle::factory(),
            'product_id' => Product::factory(),
            'feeding_date' => fake()->date(),
            'feeding_time' => fake()->time('H:i'),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_cost' => $quantity * $unitPrice,
            'method' => fake()->randomElement([
                'manual',
                'automatic',
            ]),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
