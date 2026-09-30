<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_payment_api_completes_partial_sale(): void
    {
        $user = User::factory()->create();

        $sale = Sale::create([
            'invoice_number' => 'INV-API-001',
            'sale_date' => '2026-09-30',
            'subtotal' => 1500000,
            'discount' => 0,
            'total' => 1500000,
            'paid_amount' => 1000000,
            'payment_status' => 'partial',
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                "/api/sales/{$sale->id}/payments",
                [
                    'amount' => 500000,
                    'payment_date' => '2026-09-30',
                ]
            );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Pembayaran penjualan berhasil.',
                'data' => [
                    'id' => $sale->id,
                    'invoice_number' => 'INV-API-001',
                    'total' => 1500000,
                    'paid_amount' => 1500000,
                    'remaining_amount' => 0,
                    'payment_status' => 'paid',
                ],
            ]);

        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'in',
            'category' => 'sale',
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'amount' => 500000,
        ]);
    }

    public function test_sale_payment_api_rejects_payment_exceeding_remaining(): void
    {
        $user = User::factory()->create();

        $sale = Sale::create([
            'invoice_number' => 'INV-API-002',
            'sale_date' => '2026-09-30',
            'subtotal' => 1500000,
            'discount' => 0,
            'total' => 1500000,
            'paid_amount' => 1000000,
            'payment_status' => 'partial',
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                "/api/sales/{$sale->id}/payments",
                [
                    'amount' => 600000,
                    'payment_date' => '2026-09-30',
                ]
            );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Pembayaran melebihi sisa tagihan. Sisa: 500000.',
            ]);
    }

    public function test_purchase_payment_api_completes_partial_purchase(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $purchase = Purchase::create([
            'invoice_number' => 'PUR-API-001',
            'purchase_date' => '2026-09-30',
            'subtotal' => 125000,
            'discount' => 0,
            'additional_cost' => 0,
            'total' => 125000,
            'paid_amount' => 100000,
            'payment_status' => 'partial',
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                "/api/purchases/{$purchase->id}/payments",
                [
                    'amount' => 25000,
                    'payment_date' => '2026-09-30',
                ]
            );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Pembayaran pembelian berhasil.',
                'data' => [
                    'id' => $purchase->id,
                    'invoice_number' => 'PUR-API-001',
                    'total' => 125000,
                    'paid_amount' => 125000,
                    'remaining_amount' => 0,
                    'payment_status' => 'paid',
                ],
            ]);

        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'out',
            'category' => 'purchase',
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'amount' => 25000,
        ]);
    }

    public function test_payment_api_validates_amount(): void
    {
        $user = User::factory()->create();

        $sale = Sale::create([
            'invoice_number' => 'INV-API-003',
            'sale_date' => '2026-09-30',
            'subtotal' => 1500000,
            'discount' => 0,
            'total' => 1500000,
            'paid_amount' => 1000000,
            'payment_status' => 'partial',
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                "/api/sales/{$sale->id}/payments",
                [
                    'amount' => 0,
                    'payment_date' => '2026-09-30',
                ]
            );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'amount',
            ]);
    }
}
