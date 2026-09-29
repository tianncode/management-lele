<?php

namespace App\Services;

use App\Models\Feeding;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FeedingService
{
    public function create(array $data): Feeding
    {
        return DB::transaction(function () use ($data) {
            $product = Product::query()
                ->lockForUpdate()
                ->findOrFail($data['product_id']);

            $quantity = (float) $data['quantity'];
            $unitPrice = (float) $data['unit_price'];
            $totalCost = $quantity * $unitPrice;

            if ((float) $product->current_stock < $quantity) {
                throw new RuntimeException(
                    "Stok {$product->name} tidak mencukupi. " .
                        "Stok tersedia: {$product->current_stock} {$product->unit}."
                );
            }

            /*
             * Simpan penggunaan pakan.
             */
            $feeding = Feeding::create([
                'fish_cycle_id' => $data['fish_cycle_id'],
                'product_id' => $data['product_id'],
                'feeding_date' => $data['feeding_date'],
                'feeding_time' => $data['feeding_time'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_cost' => $totalCost,
                'notes' => $data['notes'] ?? null,
            ]);

            /*
             * Kurangi stok produk.
             */
            $product->decrement(
                'current_stock',
                $quantity
            );

            /*
             * Catat pergerakan stok.
             */
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_value' => $totalCost,
                'reference_type' => Feeding::class,
                'reference_id' => $feeding->id,
                'movement_date' => $data['feeding_date'],
                'notes' => 'Pemakaian pakan untuk siklus ' .
                    $feeding->fishCycle->code,
            ]);

            return $feeding->load([
                'fishCycle',
                'product',
            ]);
        });
    }
}
