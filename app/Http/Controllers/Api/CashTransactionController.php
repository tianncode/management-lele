<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashTransactionRequest;
use App\Http\Requests\UpdateCashTransactionRequest;
use App\Http\Resources\CashTransactionResource;
use App\Models\CashTransaction;
use App\Services\CashService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CashTransactionController extends Controller
{
    public function __construct(
        protected CashService $cashService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $transactions = CashTransaction::query()
            ->latest('transaction_date')
            ->paginate(20);

        return CashTransactionResource::collection(
            $transactions
        );
    }

    public function balance(): JsonResponse
    {
        $income = (float) CashTransaction::query()
            ->where('type', 'in')
            ->sum('amount');

        $expense = (float) CashTransaction::query()
            ->where('type', 'out')
            ->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'income' => $income,
                'expense' => $expense,
                'balance' => $income - $expense,
            ],
        ]);
    }

    public function store(
        StoreCashTransactionRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $transaction = $data['type'] === 'in'
            ? $this->cashService->income(
                amount: (float) $data['amount'],
                category: $data['category'],
                description: $data['description'],
                userId: $request->user()?->id,
                transactionDate: $data['transaction_date']
            )
            : $this->cashService->expense(
                amount: (float) $data['amount'],
                category: $data['category'],
                description: $data['description'],
                userId: $request->user()?->id,
                transactionDate: $data['transaction_date']
            );

        return response()->json([
            'success' => true,
            'data' => new CashTransactionResource($transaction),
        ], 201);
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
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'Update transaksi kas dinonaktifkan. Koreksi transaksi harus dilakukan melalui transaksi sumber.',
        ], 422);
    }

    public function destroy(
        CashTransaction $cashTransaction
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'Penghapusan transaksi kas dinonaktifkan. Hapus atau koreksi transaksi melalui transaksi sumber.',
        ], 422);
    }
}
