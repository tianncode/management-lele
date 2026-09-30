<?php

namespace App\Services;

use App\Models\CashTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CashService
{
    public function income(
        float $amount,
        string $category,
        string $description,
        ?Model $reference = null,
        ?int $userId = null,
        ?string $transactionDate = null
    ): CashTransaction {
        return $this->create(
            'in',
            $amount,
            $category,
            $description,
            $reference,
            $userId,
            $transactionDate
        );
    }

    public function expense(
        float $amount,
        string $category,
        string $description,
        ?Model $reference = null,
        ?int $userId = null,
        ?string $transactionDate = null
    ): CashTransaction {
        return $this->create(
            'out',
            $amount,
            $category,
            $description,
            $reference,
            $userId,
            $transactionDate
        );
    }

    private function create(
        string $type,
        float $amount,
        string $category,
        string $description,
        ?Model $reference,
        ?int $userId,
        ?string $transactionDate
    ): CashTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Jumlah transaksi harus lebih besar dari 0.'
            );
        }

        if (!in_array($type, ['in', 'out'], true)) {
            throw new InvalidArgumentException(
                'Tipe transaksi kas tidak valid.'
            );
        }

        return DB::transaction(function () use (
            $type,
            $amount,
            $category,
            $description,
            $reference,
            $userId,
            $transactionDate
        ) {
            return CashTransaction::create([
                'transaction_date' =>
                $transactionDate ?? now()->toDateString(),

                'type' => $type,
                'category' => $category,

                'reference_type' =>
                $reference?->getMorphClass(),

                'reference_id' =>
                $reference?->getKey(),

                'description' => $description,
                'amount' => $amount,
                'created_by' => $userId,
            ]);
        });
    }

    public function balance(): float
    {
        $income = CashTransaction::where('type', 'in')
            ->sum('amount');

        $expense = CashTransaction::where('type', 'out')
            ->sum('amount');

        return (float) $income - (float) $expense;
    }
}
