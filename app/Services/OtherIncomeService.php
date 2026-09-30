<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\OtherIncome;
use Illuminate\Support\Facades\DB;

class OtherIncomeService
{
    public function __construct(
        protected CashService $cashService
    ) {}

    public function create(
        array $data,
        ?int $userId = null
    ): OtherIncome {
        return DB::transaction(function () use (
            $data,
            $userId
        ) {
            $userId ??= auth()->id();

            $income = OtherIncome::create([
                'income_date' => $data['income_date'],
                'category' => $data['category'],
                'description' => $data['description'],
                'amount' => (float) $data['amount'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $this->cashService->income(
                amount: (float) $income->amount,
                category: 'other_income',
                description: $income->description,
                reference: $income,
                userId: $userId,
                transactionDate: $income->income_date->format('Y-m-d')
            );

            return $income;
        });
    }

    public function update(
        OtherIncome $income,
        array $data
    ): OtherIncome {
        return DB::transaction(function () use (
            $income,
            $data
        ) {
            $income->update([
                'income_date' => $data['income_date'],
                'category' => $data['category'],
                'description' => $data['description'],
                'amount' => (float) $data['amount'],
                'notes' => $data['notes'] ?? null,
            ]);

            $cashTransaction = $this->cashTransactionFor($income);

            if ($cashTransaction) {
                $cashTransaction->update([
                    'transaction_date' => $income->income_date,
                    'category' => 'other_income',
                    'description' => $income->description,
                    'amount' => $income->amount,
                ]);
            }

            return $income->refresh();
        });
    }

    public function delete(
        OtherIncome $income
    ): void {
        DB::transaction(function () use ($income) {
            $cashTransaction = $this->cashTransactionFor($income);

            if ($cashTransaction) {
                $cashTransaction->delete();
            }

            $income->delete();
        });
    }

    protected function cashTransactionFor(
        OtherIncome $income
    ): ?CashTransaction {
        return CashTransaction::query()
            ->whereIn('reference_type', [
                'other_income',
                $income->getMorphClass(),
            ])
            ->where('reference_id', $income->getKey())
            ->where('type', 'in')
            ->where('category', 'other_income')
            ->first();
    }
}
