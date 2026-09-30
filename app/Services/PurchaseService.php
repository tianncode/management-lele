<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Services\CashService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseService
{
    public function __construct(
        protected StockService $stockService,
        protected CashService $cashService
    ) {}

    public function create(
        array $data,
        ?int $userId = null
    ): Purchase {
        return DB::transaction(function () use (
            $data,
            $userId
        ) {
            /*
             * Hitung subtotal dari seluruh item.
             */
            $subtotal = 0;

            foreach ($data['items'] as $item) {
                $subtotal +=
                    (float) $item['quantity']
                    * (float) $item['unit_price'];
            }

            $discount = (float) ($data['discount'] ?? 0);

            $additionalCost =
                (float) ($data['additional_cost'] ?? 0);

            /*
             * Total pembelian.
             */
            $total =
                $subtotal
                - $discount
                + $additionalCost;

            if ($total < 0) {
                throw new RuntimeException(
                    'Total pembelian tidak boleh negatif.'
                );
            }

            /*
             * Jumlah yang benar-benar dibayar.
             */
            $paidAmount = (float) ($data['paid_amount'] ?? 0);

            /*
             * Validasi pembayaran berdasarkan status.
             */
            $this->validatePayment(
                paymentStatus: $data['payment_status'],
                paidAmount: $paidAmount,
                total: $total
            );

            /*
             * Simpan purchase.
             */
            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'invoice_number' => $data['invoice_number'],
                'purchase_date' => $data['purchase_date'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'additional_cost' => $additionalCost,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'payment_status' => $data['payment_status'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            /*
             * Simpan item dan tambahkan stok.
             */
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
                    ->findOrFail($item['product_id']);

                $this->stockService->increase(
                    product: $product,
                    quantity: $quantity,
                    unitPrice: $unitPrice,
                    reference: $purchase,
                    notes: 'Pembelian ' .
                        $purchase->invoice_number,
                    userId: $userId,
                    movementDate: $data['purchase_date']
                );
            }

            /*
             * Catat pengeluaran kas hanya sebesar
             * nominal yang benar-benar dibayar.
             *
             * unpaid  = 0
             * partial = sebagian
             * paid    = seluruh total
             */
            if ($paidAmount > 0) {
                $this->cashService->expense(
                    amount: $paidAmount,
                    category: 'purchase',
                    description: 'Pembayaran pembelian ' .
                        $purchase->invoice_number,
                    reference: $purchase,
                    userId: $userId,
                    transactionDate: $data['purchase_date']
                );
            }

            return $purchase->load([
                'supplier',
                'items.product',
            ]);
        });
    }

    private function validatePayment(
        string $paymentStatus,
        float $paidAmount,
        float $total
    ): void {
        if ($paidAmount < 0) {
            throw new RuntimeException(
                'Jumlah pembayaran tidak boleh negatif.'
            );
        }

        if ($paidAmount > $total) {
            throw new RuntimeException(
                'Jumlah pembayaran tidak boleh melebihi total pembelian.'
            );
        }

        switch ($paymentStatus) {
            case 'unpaid':
                if ($paidAmount != 0) {
                    throw new RuntimeException(
                        'Pembelian dengan status unpaid harus memiliki paid_amount 0.'
                    );
                }
                break;

            case 'partial':
                if ($paidAmount <= 0 || $paidAmount >= $total) {
                    throw new RuntimeException(
                        'Pembelian dengan status partial harus memiliki paid_amount lebih dari 0 dan kurang dari total.'
                    );
                }
                break;

            case 'paid':
                if ($paidAmount != $total) {
                    throw new RuntimeException(
                        'Pembelian dengan status paid harus memiliki paid_amount sama dengan total.'
                    );
                }
                break;

            default:
                throw new RuntimeException(
                    'Status pembayaran tidak valid.'
                );
        }
    }
}
