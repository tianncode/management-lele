<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\Sale;
use App\Services\CashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CashServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_income_creates_in_transaction(): void
    {
        $transaction = app(CashService::class)->income(
            amount: 500000,
            category: 'sale',
            description: 'Pembayaran penjualan',
            transactionDate: '2026-09-30'
        );

        $this->assertEquals(
            '2026-09-30',
            $transaction->transaction_date->format('Y-m-d')
        );

        $this->assertEquals('in', $transaction->type);
        $this->assertEquals('sale', $transaction->category);
        $this->assertEquals(
            'Pembayaran penjualan',
            $transaction->description
        );
        $this->assertEquals(
            500000,
            (float) $transaction->amount
        );
    }

    public function test_cash_expense_creates_out_transaction(): void
    {
        $transaction = app(CashService::class)->expense(
            amount: 250000,
            category: 'purchase',
            description: 'Pembayaran pembelian',
            transactionDate: '2026-09-30'
        );

        $this->assertEquals(
            '2026-09-30',
            $transaction->transaction_date->format('Y-m-d')
        );

        $this->assertEquals('out', $transaction->type);
        $this->assertEquals('purchase', $transaction->category);
        $this->assertEquals(
            'Pembayaran pembelian',
            $transaction->description
        );
        $this->assertEquals(
            250000,
            (float) $transaction->amount
        );
    }

    public function test_cash_balance_returns_income_minus_expense(): void
    {
        $cashService = app(CashService::class);

        $cashService->income(
            amount: 1000000,
            category: 'sale',
            description: 'Penjualan'
        );

        $cashService->expense(
            amount: 350000,
            category: 'purchase',
            description: 'Pembelian'
        );

        $cashService->expense(
            amount: 150000,
            category: 'expense',
            description: 'Biaya operasional'
        );

        $this->assertEquals(
            500000,
            $cashService->balance()
        );
    }

    public function test_cash_transaction_rejects_zero_or_negative_amount(): void
    {
        $cashService = app(CashService::class);

        $this->expectException(InvalidArgumentException::class);

        $cashService->income(
            amount: 0,
            category: 'sale',
            description: 'Invalid transaction'
        );
    }

    public function test_cash_transaction_stores_polymorphic_reference(): void
    {
        $sale = Sale::create([
            'invoice_number' => 'INV-CASH-001',
            'sale_date' => '2026-09-30',
            'subtotal' => 500000,
            'discount' => 0,
            'total' => 500000,
            'paid_amount' => 500000,
            'payment_status' => 'paid',
        ]);

        $transaction = app(CashService::class)->income(
            amount: 500000,
            category: 'sale',
            description: 'Pembayaran penjualan INV-CASH-001',
            reference: $sale,
            transactionDate: '2026-09-30'
        );

        $this->assertDatabaseHas('cash_transactions', [
            'id' => $transaction->id,
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'amount' => 500000,
        ]);
    }
}
