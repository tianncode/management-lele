<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function create(
        array $data,
        ?int $userId = null
    ): Expense {
        return DB::transaction(function () use ($data, $userId) {
            $expense = Expense::create([
                'category_id' => $data['category_id'],
                'expense_date' => $data['expense_date'],
                'description' => $data['description'],
                'amount' => $data['amount'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            app(CashService::class)->expense(
                amount: (float) $expense->amount,
                category: 'expense',
                description: $expense->description,
                reference: $expense,
                userId: $userId,
                transactionDate: $expense->expense_date->format('Y-m-d'),
            );

            return $expense->load('category');
        });
    }
}
