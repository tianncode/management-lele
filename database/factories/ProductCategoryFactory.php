<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition(): array
    {
        return [
            'name' => 'Pakan Ikan ' . fake()->unique()->numberBetween(1, 999),
            'description' => 'Kategori produk pakan ikan untuk kebutuhan budidaya.',
            'is_active' => true,
        ];
    }
}
