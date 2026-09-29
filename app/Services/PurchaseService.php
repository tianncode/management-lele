<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $subtotal = 0;

            foreach ($data['items'] as $item) {
                $subtotal +=
                    (float) $item['quantity']
                    * (float) $item['unit_price'];
            }

            $discount = (float) ($data['discount'] ?? 0);

            $additionalCost =
                (float) ($data['additional_cost'] ?? 0);

            $total =
                $subtotal
                - $discount
                + $additionalCost;

            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'invoice_number' => $data['invoice_number'],
                'purchase_date' => $data['purchase_date'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'additional_cost' => $additionalCost,
                'total' => $total,
                'payment_status' => $data['payment_status'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];

                $itemSubtotal =
                    $quantity * $unitPrice;

                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                ]);

                $product = Product::query()
                    ->lockForUpdate()
                    ->findOrFail($item['product_id']);

                /*
                 * Tambahkan stok.
                 */
                $product->increment(
                    'current_stock',
                    $quantity
                );

                /*
                 * Catat stock movement IN.
                 */
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_value' => $itemSubtotal,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'movement_date' => $data['purchase_date'],
                    'notes' => 'Pembelian ' .
                        $purchase->invoice_number,
                ]);
            }

            return $purchase->load([
                'supplier',
                'items.product',
            ]);
        });
    }
}
