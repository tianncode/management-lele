<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashTransactionRequest;
use App\Http\Requests\UpdateCashTransactionRequest;
use App\Http\Resources\CashTransactionResource;
use App\Models\CashTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CashTransactionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $transactions = CashTransaction::query()
            ->latest('transaction_date')
            ->paginate(20);

        return CashTransactionResource::collection(
            $transactions
        );
    }

    public function store(
        StoreCashTransactionRequest $request
    ): CashTransactionResource {
        $transaction = CashTransaction::create(
            $request->validated()
        );

        return new CashTransactionResource(
            $transaction
        );
    }

    public function show(
        CashTransaction $cashTransaction
    ): CashTransactionResource {
        return new CashTransactionResource(
            $cashTransaction
        );
    }

    public function update(
        UpdateCashTransactionRequest $request,
        CashTransaction $cashTransaction
    ): CashTransactionResource {
        $cashTransaction->update(
            $request->validated()
        );

        return new CashTransactionResource(
            $cashTransaction->refresh()
        );
    }

    public function destroy(
        CashTransaction $cashTransaction
    ): JsonResponse {
        $cashTransaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi kas berhasil dihapus.',
        ]);
    }
}
