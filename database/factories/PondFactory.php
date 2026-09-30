<?php

namespace Database\Factories;

use App\Models\Pond;
use Illuminate\Database\Eloquent\Factories\Factory;

class PondFactory extends Factory
{
    protected $model = Pond::class;

    public function definition(): array
    {
        return [
            'code' => 'POND-' . fake()->unique()->numerify('###'),
            'name' => 'Kolam ' . fake()->unique()->numberBetween(1, 999),
            'length' => 10.00,
            'width' => 5.00,
            'depth' => 1.50,
            'volume' => 75.000,
            'status' => 'cultivation',
            'notes' => null,
        ];
    }
}
