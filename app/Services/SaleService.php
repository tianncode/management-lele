<?php

namespace App\Services;

use App\Models\Harvest;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleService
{
    public function __construct(
        protected CashService $cashService
    ) {}

    public function create(
        array $data,
        ?int $userId = null
    ): Sale {
        return DB::transaction(function () use (
            $data,
            $userId
        ) {
            $subtotal = 0;

            foreach ($data['items'] as $item) {
                $subtotal +=
                    (float) $item['quantity']
                    * (float) $item['unit_price'];
            }

            $discount = (float) ($data['discount'] ?? 0);

            $total = $subtotal - $discount;

            if ($total < 0) {
                throw new RuntimeException(
                    'Total penjualan tidak boleh negatif.'
                );
            }

            $paidAmount = (float) $data['paid_amount'];

            if ($paidAmount > $total) {
                throw new RuntimeException(
                    "Jumlah pembayaran tidak boleh melebihi total penjualan. " .
                        "Total: {$total}."
                );
            }

            $paymentStatus = match (true) {
                $paidAmount <= 0 => 'unpaid',
                $paidAmount < $total => 'partial',
                default => 'paid',
            };

            $sale = Sale::create([
                'customer_id' => $data['customer_id'] ?? null,
                'invoice_number' => $data['invoice_number'],
                'sale_date' => $data['sale_date'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'payment_status' => $paymentStatus,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId ?? auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $harvest = Harvest::query()
                    ->lockForUpdate()
                    ->findOrFail($item['harvest_id']);

                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];

                $itemSubtotal =
                    $quantity * $unitPrice;

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
            }

            if ($paidAmount > 0) {
                $this->cashService->income(
                    amount: $paidAmount,
                    category: 'sale',
                    description: 'Pembayaran penjualan ' .
                        $sale->invoice_number,
                    reference: $sale,
                    userId: $userId ?? auth()->id(),
                    transactionDate: $data['sale_date']
                );
            }

            return $sale->load([
                'customer',
                'items.harvest',
            ]);
        });
    }
}
