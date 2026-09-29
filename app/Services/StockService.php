<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockService
{
    public function increase(
        Product $product,
        float $quantity,
        ?float $unitPrice = null,
        ?Model $reference = null,
        ?string $notes = null,
        ?int $userId = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException(
                'Quantity harus lebih besar dari 0.'
            );
        }

        return DB::transaction(function () use (
            $product,
            $quantity,
            $unitPrice,
            $reference,
            $notes,
            $userId
        ) {
            $movement = StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_value' => $unitPrice !== null
                    ? $quantity * $unitPrice
                    : null,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'movement_date' => now()->toDateString(),
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            $product->increment('current_stock', $quantity);

            return $movement;
        });
    }

    public function decrease(
        Product $product,
        float $quantity,
        ?float $unitPrice = null,
        ?Model $reference = null,
        ?string $notes = null,
        ?int $userId = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException(
                'Quantity harus lebih besar dari 0.'
            );
        }

        if ($product->current_stock < $quantity) {
            throw new InvalidArgumentException(
                "Stok {$product->name} tidak mencukupi."
            );
        }

        return DB::transaction(function () use (
            $product,
            $quantity,
            $unitPrice,
            $reference,
            $notes,
            $userId
        ) {
            $movement = StockMovement::create([
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_value' => $unitPrice !== null
                    ? $quantity * $unitPrice
                    : null,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'movement_date' => now()->toDateString(),
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            $product->decrement('current_stock', $quantity);

            return $movement;
        });
    }

    public function adjustment(
        Product $product,
        float $quantity,
        ?string $notes = null,
        ?int $userId = null
    ): StockMovement {
        if ($quantity == 0) {
            throw new InvalidArgumentException(
                'Adjustment tidak boleh 0.'
            );
        }

        return DB::transaction(function () use (
            $product,
            $quantity,
            $notes,
            $userId
        ) {
            $newStock = $product->current_stock + $quantity;

            if ($newStock < 0) {
                throw new InvalidArgumentException(
                    'Hasil adjustment tidak boleh membuat stok negatif.'
                );
            }

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'type' => 'adjustment',
                'quantity' => $quantity,
                'unit_price' => null,
                'total_value' => null,
                'reference_type' => null,
                'reference_id' => null,
                'movement_date' => now()->toDateString(),
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            $product->update([
                'current_stock' => $newStock,
            ]);

            return $movement;
        });
    }
}
