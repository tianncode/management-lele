<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => \App\Models\ProductCategory::factory(),
            'code' => 'FEED-' . fake()->unique()->numerify('###'),
            'name' => 'Pakan Lele',
            'unit' => 'kg',
            'current_stock' => 1000,
            'minimum_stock' => 100,
            'average_price' => 10000,
            'is_active' => true,
        ];
    }
}
