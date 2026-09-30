<?php

namespace App\Services;

use App\Models\Feeding;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class FeedingService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function create(
        array $data,
        ?int $userId = null
    ): Feeding {
        return DB::transaction(function () use (
            $data,
            $userId
        ) {
            $product = Product::query()
                ->findOrFail($data['product_id']);

            $quantity = (float) $data['quantity'];
            $unitPrice = (float) $data['unit_price'];

            $totalCost = $quantity * $unitPrice;

            /*
             * Simpan penggunaan pakan.
             */
            $feeding = Feeding::create([
                'fish_cycle_id' => $data['fish_cycle_id'],
                'product_id' => $product->id,
                'feeding_date' => $data['feeding_date'],
                'feeding_time' => $data['feeding_time'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_cost' => $totalCost,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            /*
             * Kurangi stok melalui StockService.
             *
             * StockService bertanggung jawab terhadap:
             * - lock stok
             * - validasi stok
             * - update current_stock
             * - pencatatan stock movement
             */
            $this->stockService->decrease(
                product: $product,
                quantity: $quantity,
                unitPrice: $unitPrice,
                reference: $feeding,
                notes: 'Pemakaian pakan untuk siklus ' .
                    $feeding->fishCycle->code,
                userId: $userId,
                movementDate: $data['feeding_date']
            );

            return $feeding->load([
                'fishCycle',
                'product',
            ]);
        });
    }
}
