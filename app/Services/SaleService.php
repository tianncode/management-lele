<?php

namespace App\Services;

use App\Models\Harvest;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleService
{
    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $subtotal = 0;

            $sale = Sale::create([
                'customer_id' => $data['customer_id'] ?? null,
                'invoice_number' => $data['invoice_number'],
                'sale_date' => $data['sale_date'],
                'subtotal' => 0,
                'discount' => $data['discount'] ?? 0,
                'total' => 0,
                'payment_status' => $data['payment_status'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $harvest = Harvest::query()
                    ->lockForUpdate()
                    ->findOrFail($item['harvest_id']);

                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];

                /*
                 * Total penjualan item.
                 *
                 * Quantity menggunakan kilogram.
                 */
                $itemSubtotal = $quantity * $unitPrice;

                $alreadySold = (float) $harvest
                    ->saleItems()
                    ->sum('quantity');

                $availableWeight =
                    (float) $harvest->total_weight
                    - $alreadySold;

                if ($quantity > $availableWeight) {
                    throw new RuntimeException(
                        "Berat yang dijual melebihi hasil panen yang tersedia. " .
                            "Tersedia: {$availableWeight} kg."
                    );
                }

                $sale->items()->create([
                    'harvest_id' => $harvest->id,
                    'description' =>
                    $item['description']
                        ?? 'Penjualan hasil panen',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                ]);

                $subtotal += $itemSubtotal;
            }

            $discount = (float) ($data['discount'] ?? 0);

            $total = $subtotal - $discount;

            $sale->update([
                'subtotal' => $subtotal,
                'total' => $total,
            ]);

            return $sale->load([
                'customer',
                'items.harvest',
            ]);
        });
    }
}
