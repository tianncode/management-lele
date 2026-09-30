<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Services\ExpenseService;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseController extends Controller
{

    public function __construct(
        protected ExpenseService $expenseService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $expenses = Expense::query()
            ->with('category')
            ->latest('expense_date')
            ->paginate(15);

        return ExpenseResource::collection($expenses);
    }

    public function store(
        StoreExpenseRequest $request
    ): ExpenseResource {
        $expense = $this->expenseService->create(
            $request->validated(),
            $request->user()?->id
        );

        $expense->load('category');

        return new ExpenseResource($expense);
    }

    public function show(
        Expense $expense
    ): ExpenseResource {
        $expense->load('category');

        return new ExpenseResource($expense);
    }

    public function update(
        UpdateExpenseRequest $request,
        Expense $expense
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'Update expense akan diaktifkan setelah mekanisme koreksi transaksi keuangan selesai.',
        ], 422);
    }

    public function destroy(
        Expense $expense
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'Penghapusan expense akan diaktifkan setelah mekanisme koreksi transaksi keuangan selesai.',
        ], 422);
    }
}
