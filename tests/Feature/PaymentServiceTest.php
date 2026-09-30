<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\Sale;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_payment_completes_partial_sale(): void
    {
        $sale = Sale::create([
            'invoice_number' => 'INV-PAY-001',
            'sale_date' => '2026-09-30',
            'subtotal' => 1500000,
            'discount' => 0,
            'total' => 1500000,
            'paid_amount' => 1000000,
            'payment_status' => 'partial',
        ]);

        $updatedSale = app(PaymentService::class)->paySale(
            sale: $sale,
            amount: 500000,
            paymentDate: '2026-09-30'
        );

        $this->assertEquals(
            1500000,
            (float) $updatedSale->paid_amount
        );

        $this->assertEquals(
            'paid',
            $updatedSale->payment_status
        );

        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'in',
            'category' => 'sale',
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'amount' => 500000,
        ]);
    }

    public function test_sale_payment_cannot_exceed_remaining_amount(): void
    {
        $sale = Sale::create([
            'invoice_number' => 'INV-PAY-002',
            'sale_date' => '2026-09-30',
            'subtotal' => 1500000,
            'discount' => 0,
            'total' => 1500000,
            'paid_amount' => 1000000,
            'payment_status' => 'partial',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(PaymentService::class)->paySale(
            sale: $sale,
            amount: 600000,
            paymentDate: '2026-09-30'
        );
    }

    public function test_purchase_payment_completes_partial_purchase(): void
    {
        $purchase = Purchase::create([
            'invoice_number' => 'PUR-PAY-001',
            'purchase_date' => '2026-09-30',
            'subtotal' => 125000,
            'discount' => 0,
            'additional_cost' => 0,
            'total' => 125000,
            'paid_amount' => 100000,
            'payment_status' => 'partial',
        ]);

        $updatedPurchase = app(PaymentService::class)->payPurchase(
            purchase: $purchase,
            amount: 25000,
            paymentDate: '2026-09-30'
        );

        $this->assertEquals(
            125000,
            (float) $updatedPurchase->paid_amount
        );

        $this->assertEquals(
            'paid',
            $updatedPurchase->payment_status
        );

        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'out',
            'category' => 'purchase',
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'amount' => 25000,
        ]);
    }

    public function test_purchase_payment_cannot_exceed_remaining_amount(): void
    {
        $purchase = Purchase::create([
            'invoice_number' => 'PUR-PAY-002',
            'purchase_date' => '2026-09-30',
            'subtotal' => 125000,
            'discount' => 0,
            'additional_cost' => 0,
            'total' => 125000,
            'paid_amount' => 100000,
            'payment_status' => 'partial',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(PaymentService::class)->payPurchase(
            purchase: $purchase,
            amount: 30000,
            paymentDate: '2026-09-30'
        );
    }
}
