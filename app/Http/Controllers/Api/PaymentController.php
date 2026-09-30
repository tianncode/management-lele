<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function sale(
        StorePaymentRequest $request,
        Sale $sale
    ): JsonResponse {
        $sale = $this->paymentService->paySale(
            sale: $sale,
            amount: (float) $request->validated('amount'),
            userId: auth()->id(),
            paymentDate: $request->validated('payment_date')
        );

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran penjualan berhasil.',
            'data' => [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'total' => (float) $sale->total,
                'paid_amount' => (float) $sale->paid_amount,
                'remaining_amount' => max(
                    0,
                    (float) $sale->total - (float) $sale->paid_amount
                ),
                'payment_status' => $sale->payment_status,
            ],
        ]);
    }

    public function purchase(
        StorePaymentRequest $request,
        Purchase $purchase
    ): JsonResponse {
        $purchase = $this->paymentService->payPurchase(
            purchase: $purchase,
            amount: (float) $request->validated('amount'),
            userId: auth()->id(),
            paymentDate: $request->validated('payment_date')
        );

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran pembelian berhasil.',
            'data' => [
                'id' => $purchase->id,
                'invoice_number' => $purchase->invoice_number,
                'total' => (float) $purchase->total,
                'paid_amount' => (float) $purchase->paid_amount,
                'remaining_amount' => max(
                    0,
                    (float) $purchase->total -
                        (float) $purchase->paid_amount
                ),
                'payment_status' => $purchase->payment_status,
            ],
        ]);
    }
}
