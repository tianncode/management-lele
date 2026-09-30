<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(
        protected CashService $cashService
    ) {}

    public function paySale(
        Sale $sale,
        float $amount,
        ?int $userId = null,
        ?string $paymentDate = null
    ): Sale {
        return DB::transaction(function () use (
            $sale,
            $amount,
            $userId,
            $paymentDate
        ) {
            if ($amount <= 0) {
                throw new InvalidArgumentException(
                    'Jumlah pembayaran harus lebih besar dari 0.'
                );
            }

            $sale = Sale::query()
                ->lockForUpdate()
                ->findOrFail($sale->id);

            $total = (float) $sale->total;
            $paidAmount = (float) $sale->paid_amount;
            $remaining = $total - $paidAmount;

            if ($remaining <= 0) {
                throw new InvalidArgumentException(
                    'Penjualan sudah lunas.'
                );
            }

            if ($amount > $remaining) {
                throw new InvalidArgumentException(
                    "Pembayaran melebihi sisa tagihan. " .
                        "Sisa: {$remaining}."
                );
            }

            $newPaidAmount = $paidAmount + $amount;

            $paymentStatus = $newPaidAmount >= $total
                ? 'paid'
                : 'partial';

            $sale->update([
                'paid_amount' => $newPaidAmount,
                'payment_status' => $paymentStatus,
            ]);

            $this->cashService->income(
                amount: $amount,
                category: 'sale',
                description: 'Pembayaran penjualan ' .
                    $sale->invoice_number,
                reference: $sale,
                userId: $userId,
                transactionDate: $paymentDate
            );

            return $sale->refresh();
        });
    }

    public function payPurchase(
        Purchase $purchase,
        float $amount,
        ?int $userId = null,
        ?string $paymentDate = null
    ): Purchase {
        return DB::transaction(function () use (
            $purchase,
            $amount,
            $userId,
            $paymentDate
        ) {
            if ($amount <= 0) {
                throw new InvalidArgumentException(
                    'Jumlah pembayaran harus lebih besar dari 0.'
                );
            }

            $purchase = Purchase::query()
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            $total = (float) $purchase->total;
            $paidAmount = (float) $purchase->paid_amount;
            $remaining = $total - $paidAmount;

            if ($remaining <= 0) {
                throw new InvalidArgumentException(
                    'Pembelian sudah lunas.'
                );
            }

            if ($amount > $remaining) {
                throw new InvalidArgumentException(
                    "Pembayaran melebihi sisa hutang. " .
                        "Sisa: {$remaining}."
                );
            }

            $newPaidAmount = $paidAmount + $amount;

            $paymentStatus = $newPaidAmount >= $total
                ? 'paid'
                : 'partial';

            $purchase->update([
                'paid_amount' => $newPaidAmount,
                'payment_status' => $paymentStatus,
            ]);

            $this->cashService->expense(
                amount: $amount,
                category: 'purchase',
                description: 'Pembayaran pembelian ' .
                    $purchase->invoice_number,
                reference: $purchase,
                userId: $userId,
                transactionDate: $paymentDate
            );

            return $purchase->refresh();
        });
    }
}
