<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FishCycle;
use App\Models\Harvest;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($this->user, 'sanctum');
    }

    protected function createSale(
        float $total = 500000,
        float $paidAmount = 0
    ): Sale {
        $customer = Customer::create([
            'code' => 'CUST-' . uniqid(),
            'name' => 'Customer Test',
            'phone' => '081234567890',
            'address' => 'Bandung',
            'notes' => null,
        ]);

        return Sale::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-PAY-' . uniqid(),
            'sale_date' => '2026-09-30',
            'subtotal' => $total,
            'discount' => 0,
            'total' => $total,
            'paid_amount' => $paidAmount,
            'payment_status' => $paidAmount >= $total
                ? 'paid'
                : ($paidAmount > 0 ? 'partial' : 'unpaid'),
            'notes' => null,
            'created_by' => $this->user->id,
        ]);
    }

    protected function createPurchase(
        float $total = 500000,
        float $paidAmount = 0
    ): Purchase {
        $supplier = Supplier::create([
            'code' => 'SUP-' . uniqid(),
            'name' => 'Supplier Test',
            'phone' => '081234567890',
            'address' => 'Bandung',
            'notes' => null,
        ]);

        return Purchase::create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-PUR-PAY-' . uniqid(),
            'purchase_date' => '2026-09-30',
            'subtotal' => $total,
            'discount' => 0,
            'additional_cost' => 0,
            'total' => $total,
            'paid_amount' => $paidAmount,
            'payment_status' => $paidAmount >= $total
                ? 'paid'
                : ($paidAmount > 0 ? 'partial' : 'unpaid'),
            'notes' => null,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_sale_payment_updates_paid_amount(): void
    {
        $sale = $this->createSale(
            total: 500000,
            paidAmount: 0
        );

        $response = $this->postJson(
            "/api/sales/{$sale->id}/payments",
            [
                'amount' => 200000,
                'payment_date' => '2026-09-30',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $sale->id)
            ->assertJsonPath('data.total', 500000)
            ->assertJsonPath('data.paid_amount', 200000)
            ->assertJsonPath('data.remaining_amount', 300000)
            ->assertJsonPath('data.payment_status', 'partial');

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'paid_amount' => 200000,
            'payment_status' => 'partial',
        ]);
    }

    public function test_sale_payment_can_complete_sale(): void
    {
        $sale = $this->createSale(
            total: 500000,
            paidAmount: 200000
        );

        $response = $this->postJson(
            "/api/sales/{$sale->id}/payments",
            [
                'amount' => 300000,
                'payment_date' => '2026-09-30',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.paid_amount', 500000)
            ->assertJsonPath('data.remaining_amount', 0)
            ->assertJsonPath('data.payment_status', 'paid');

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'paid_amount' => 500000,
            'payment_status' => 'paid',
        ]);
    }

    public function test_sale_payment_creates_cash_in_transaction(): void
    {
        $sale = $this->createSale(
            total: 500000,
            paidAmount: 0
        );

        $response = $this->postJson(
            "/api/sales/{$sale->id}/payments",
            [
                'amount' => 200000,
                'payment_date' => '2026-09-30',
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'in',
            'category' => 'sale',
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'description' => 'Pembayaran penjualan ' .
                $sale->invoice_number,
            'amount' => 200000,
            'transaction_date' => '2026-09-30 00:00:00',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_sale_payment_cannot_exceed_remaining_amount(): void
    {
        $sale = $this->createSale(
            total: 500000,
            paidAmount: 200000
        );

        $response = $this->postJson(
            "/api/sales/{$sale->id}/payments",
            [
                'amount' => 300001,
                'payment_date' => '2026-09-30',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'paid_amount' => 200000,
            'payment_status' => 'partial',
        ]);

        $this->assertDatabaseMissing('cash_transactions', [
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'amount' => 300001,
        ]);
    }

    public function test_sale_payment_cannot_pay_already_paid_sale(): void
    {
        $sale = $this->createSale(
            total: 500000,
            paidAmount: 500000
        );

        $response = $this->postJson(
            "/api/sales/{$sale->id}/payments",
            [
                'amount' => 100000,
                'payment_date' => '2026-09-30',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'paid_amount' => 500000,
            'payment_status' => 'paid',
        ]);
    }

    public function test_sale_payment_must_be_greater_than_zero(): void
    {
        $sale = $this->createSale();

        $response = $this->postJson(
            "/api/sales/{$sale->id}/payments",
            [
                'amount' => 0,
                'payment_date' => '2026-09-30',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');
    }

    public function test_sale_payment_requires_valid_data(): void
    {
        $sale = $this->createSale();

        $response = $this->postJson(
            "/api/sales/{$sale->id}/payments",
            []
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'amount',
            ]);
    }

    public function test_sale_payment_requires_valid_payment_date(): void
    {
        $sale = $this->createSale();

        $response = $this->postJson(
            "/api/sales/{$sale->id}/payments",
            [
                'amount' => 100000,
                'payment_date' => 'tanggal-salah',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('payment_date');
    }

    public function test_sale_payment_returns_404_for_non_existing_sale(): void
    {
        $response = $this->postJson(
            '/api/sales/999999/payments',
            [
                'amount' => 100000,
                'payment_date' => '2026-09-30',
            ]
        );

        $response->assertNotFound();
    }

    public function test_purchase_payment_updates_paid_amount(): void
    {
        $purchase = $this->createPurchase(
            total: 500000,
            paidAmount: 0
        );

        $response = $this->postJson(
            "/api/purchases/{$purchase->id}/payments",
            [
                'amount' => 200000,
                'payment_date' => '2026-09-30',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $purchase->id)
            ->assertJsonPath('data.total', 500000)
            ->assertJsonPath('data.paid_amount', 200000)
            ->assertJsonPath('data.remaining_amount', 300000)
            ->assertJsonPath('data.payment_status', 'partial');

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'paid_amount' => 200000,
            'payment_status' => 'partial',
        ]);
    }

    public function test_purchase_payment_can_complete_purchase(): void
    {
        $purchase = $this->createPurchase(
            total: 500000,
            paidAmount: 200000
        );

        $response = $this->postJson(
            "/api/purchases/{$purchase->id}/payments",
            [
                'amount' => 300000,
                'payment_date' => '2026-09-30',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.paid_amount', 500000)
            ->assertJsonPath('data.remaining_amount', 0)
            ->assertJsonPath('data.payment_status', 'paid');

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'paid_amount' => 500000,
            'payment_status' => 'paid',
        ]);
    }

    public function test_purchase_payment_creates_cash_out_transaction(): void
    {
        $purchase = $this->createPurchase(
            total: 500000,
            paidAmount: 0
        );

        $response = $this->postJson(
            "/api/purchases/{$purchase->id}/payments",
            [
                'amount' => 200000,
                'payment_date' => '2026-09-30',
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'out',
            'category' => 'purchase',
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'description' => 'Pembayaran pembelian ' .
                $purchase->invoice_number,
            'amount' => 200000,
            'transaction_date' => '2026-09-30 00:00:00',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_purchase_payment_cannot_exceed_remaining_amount(): void
    {
        $purchase = $this->createPurchase(
            total: 500000,
            paidAmount: 200000
        );

        $response = $this->postJson(
            "/api/purchases/{$purchase->id}/payments",
            [
                'amount' => 300001,
                'payment_date' => '2026-09-30',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'paid_amount' => 200000,
            'payment_status' => 'partial',
        ]);

        $this->assertDatabaseMissing('cash_transactions', [
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'amount' => 300001,
        ]);
    }

    public function test_purchase_payment_cannot_pay_already_paid_purchase(): void
    {
        $purchase = $this->createPurchase(
            total: 500000,
            paidAmount: 500000
        );

        $response = $this->postJson(
            "/api/purchases/{$purchase->id}/payments",
            [
                'amount' => 100000,
                'payment_date' => '2026-09-30',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'paid_amount' => 500000,
            'payment_status' => 'paid',
        ]);
    }

    public function test_purchase_payment_must_be_greater_than_zero(): void
    {
        $purchase = $this->createPurchase();

        $response = $this->postJson(
            "/api/purchases/{$purchase->id}/payments",
            [
                'amount' => 0,
                'payment_date' => '2026-09-30',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');
    }

    public function test_purchase_payment_requires_valid_data(): void
    {
        $purchase = $this->createPurchase();

        $response = $this->postJson(
            "/api/purchases/{$purchase->id}/payments",
            []
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'amount',
            ]);
    }

    public function test_purchase_payment_requires_valid_payment_date(): void
    {
        $purchase = $this->createPurchase();

        $response = $this->postJson(
            "/api/purchases/{$purchase->id}/payments",
            [
                'amount' => 100000,
                'payment_date' => 'tanggal-salah',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('payment_date');
    }

    public function test_purchase_payment_returns_404_for_non_existing_purchase(): void
    {
        $response = $this->postJson(
            '/api/purchases/999999/payments',
            [
                'amount' => 100000,
                'payment_date' => '2026-09-30',
            ]
        );

        $response->assertNotFound();
    }
}
