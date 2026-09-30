<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\CashTransaction;
use App\Services\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_creates_expense_record(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Operasional',
            'description' => 'Biaya operasional',
            'is_active' => true,
        ]);

        $expense = app(ExpenseService::class)->create([
            'category_id' => $category->id,
            'expense_date' => '2026-09-30',
            'description' => 'Biaya pakan',
            'amount' => 500000,
            'notes' => 'Pembelian pakan',
        ]);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'category_id' => $category->id,
            'description' => 'Biaya pakan',
            'amount' => 500000,
        ]);
    }

    public function test_expense_creates_cash_out_transaction(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Operasional',
            'description' => 'Biaya operasional',
            'is_active' => true,
        ]);

        $expense = app(ExpenseService::class)->create([
            'category_id' => $category->id,
            'expense_date' => '2026-09-30',
            'description' => 'Biaya listrik',
            'amount' => 250000,
        ]);

        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'out',
            'category' => 'expense',
            'reference_type' => $expense->getMorphClass(),
            'reference_id' => $expense->id,
            'description' => 'Biaya listrik',
            'amount' => 250000,
        ]);
    }

    public function test_expense_cash_transaction_uses_expense_date(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Operasional',
            'description' => 'Biaya operasional',
            'is_active' => true,
        ]);

        $expense = app(ExpenseService::class)->create([
            'category_id' => $category->id,
            'expense_date' => '2026-09-25',
            'description' => 'Biaya internet',
            'amount' => 150000,
        ]);

        $transaction = CashTransaction::query()
            ->where('reference_type', $expense->getMorphClass())
            ->where('reference_id', $expense->id)
            ->firstOrFail();

        $this->assertSame(
            '2026-09-25',
            $transaction->transaction_date->format('Y-m-d')
        );
    }

    public function test_expense_rejects_zero_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $category = ExpenseCategory::create([
            'name' => 'Operasional',
            'description' => 'Biaya operasional',
            'is_active' => true,
        ]);

        app(ExpenseService::class)->create([
            'category_id' => $category->id,
            'expense_date' => '2026-09-30',
            'description' => 'Invalid expense',
            'amount' => 0,
        ]);
    }

    public function test_expense_rejects_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $category = ExpenseCategory::create([
            'name' => 'Operasional',
            'description' => 'Biaya operasional',
            'is_active' => true,
        ]);

        app(ExpenseService::class)->create([
            'category_id' => $category->id,
            'expense_date' => '2026-09-30',
            'description' => 'Invalid expense',
            'amount' => -100000,
        ]);
    }
}
