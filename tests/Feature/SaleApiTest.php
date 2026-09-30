<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\FishCycle;
use App\Models\Harvest;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'staff',
        ]);

        $this->actingAs($this->user, 'sanctum');
    }

    protected function createHarvest(
        float $totalWeight = 100
    ): Harvest {
        $cycle = FishCycle::factory()->create([
            'initial_fish_count' => 1000,
            'status' => 'active',
        ]);

        return Harvest::create([
            'fish_cycle_id' => $cycle->id,
            'harvest_date' => '2026-09-29',
            'total_fish' => 1000,
            'total_weight' => $totalWeight,
            'average_weight' => ($totalWeight * 1000) / 1000,
            'selling_price_per_kg' => 30000,
            'estimated_revenue' => $totalWeight * 30000,
        ]);
    }

    protected function createCustomer(): Customer
    {
        return Customer::create([
            'code' => 'CUS-' . fake()->unique()->numberBetween(1000, 9999),
            'name' => 'Pelanggan Test',
            'phone' => '081234567890',
            'address' => 'Bandung',
            'notes' => null,
        ]);
    }

    protected function salePayload(
        Harvest $harvest,
        array $overrides = []
    ): array {
        return array_merge([
            'customer_id' => null,
            'invoice_number' => 'INV-' . fake()->unique()->numberBetween(10000, 99999),
            'sale_date' => '2026-09-30',
            'discount' => 0,
            'paid_amount' => 0,
            'notes' => null,
            'items' => [
                [
                    'harvest_id' => $harvest->id,
                    'description' => 'Penjualan ikan lele',
                    'quantity' => 10,
                    'unit_price' => 30000,
                ],
            ],
        ], $overrides);
    }

    public function test_can_list_sales(): void
    {
        $harvest = $this->createHarvest();

        Sale::create([
            'customer_id' => null,
            'invoice_number' => 'INV-LIST-001',
            'sale_date' => '2026-09-30',
            'subtotal' => 300000,
            'discount' => 0,
            'total' => 300000,
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
            'notes' => null,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/sales');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'customer',
                        'invoice_number',
                        'sale_date',
                        'subtotal',
                        'discount',
                        'total',
                        'payment_status',
                        'notes',
                        'items',
                        'paid_amount',
                        'remaining_amount',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_can_create_sale(): void
    {
        $harvest = $this->createHarvest(100);

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'paid_amount' => 300000,
                'items' => [
                    [
                        'harvest_id' => $harvest->id,
                        'description' => 'Ikan lele konsumsi',
                        'quantity' => 10,
                        'unit_price' => 30000,
                    ],
                ],
            ])
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.subtotal', 300000)
            ->assertJsonPath('data.discount', 0)
            ->assertJsonPath('data.total', 300000)
            ->assertJsonPath('data.paid_amount', 300000)
            ->assertJsonPath('data.remaining_amount', 0)
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.items.0.quantity', 10)
            ->assertJsonPath('data.items.0.unit_price', 30000)
            ->assertJsonPath('data.items.0.subtotal', 300000);

        $this->assertDatabaseHas('sales', [
            'invoice_number' => $response->json('data.invoice_number'),
            'total' => 300000,
            'paid_amount' => 300000,
            'payment_status' => 'paid',
        ]);

        $this->assertDatabaseHas('sale_items', [
            'harvest_id' => $harvest->id,
            'quantity' => 10,
            'unit_price' => 30000,
            'subtotal' => 300000,
        ]);
    }

    public function test_sale_can_use_customer(): void
    {
        $customer = $this->createCustomer();
        $harvest = $this->createHarvest();

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'customer_id' => $customer->id,
            ])
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.customer.id', $customer->id)
            ->assertJsonPath('data.customer.name', 'Pelanggan Test');
    }

    public function test_sale_calculates_subtotal_from_items(): void
    {
        $harvest = $this->createHarvest(100);

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'items' => [
                    [
                        'harvest_id' => $harvest->id,
                        'description' => 'Item 1',
                        'quantity' => 10,
                        'unit_price' => 30000,
                    ],
                    [
                        'harvest_id' => $harvest->id,
                        'description' => 'Item 2',
                        'quantity' => 5,
                        'unit_price' => 20000,
                    ],
                ],
            ])
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.subtotal', 400000)
            ->assertJsonPath('data.total', 400000)
            ->assertJsonPath('data.items.0.subtotal', 300000)
            ->assertJsonPath('data.items.1.subtotal', 100000);
    }

    public function test_sale_calculates_total_after_discount(): void
    {
        $harvest = $this->createHarvest();

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'discount' => 50000,
                'paid_amount' => 250000,
            ])
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.subtotal', 300000)
            ->assertJsonPath('data.discount', 50000)
            ->assertJsonPath('data.total', 250000)
            ->assertJsonPath('data.paid_amount', 250000)
            ->assertJsonPath('data.remaining_amount', 0)
            ->assertJsonPath('data.payment_status', 'paid');
    }

    public function test_unpaid_sale_has_unpaid_status(): void
    {
        $harvest = $this->createHarvest();

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'paid_amount' => 0,
            ])
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.total', 300000)
            ->assertJsonPath('data.paid_amount', 0)
            ->assertJsonPath('data.remaining_amount', 300000)
            ->assertJsonPath('data.payment_status', 'unpaid');
    }

    public function test_partial_payment_has_partial_status(): void
    {
        $harvest = $this->createHarvest();

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'paid_amount' => 100000,
            ])
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.total', 300000)
            ->assertJsonPath('data.paid_amount', 100000)
            ->assertJsonPath('data.remaining_amount', 200000)
            ->assertJsonPath('data.payment_status', 'partial');
    }

    public function test_paid_payment_has_paid_status(): void
    {
        $harvest = $this->createHarvest();

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'paid_amount' => 300000,
            ])
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.remaining_amount', 0);
    }

    public function test_paid_amount_cannot_exceed_total(): void
    {
        $harvest = $this->createHarvest();

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'paid_amount' => 300001,
            ])
        );

        $response
            ->assertStatus(500)
            ->assertJsonFragment([
                'message' =>
                'Jumlah pembayaran tidak boleh melebihi total penjualan. Total: 300000.',
            ]);

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_discount_cannot_make_total_negative(): void
    {
        $harvest = $this->createHarvest();

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'discount' => 300001,
            ])
        );

        $response
            ->assertStatus(500)
            ->assertJsonFragment([
                'message' => 'Total penjualan tidak boleh negatif.',
            ]);

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_sale_cannot_exceed_available_harvest_weight(): void
    {
        $harvest = $this->createHarvest(20);

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'items' => [
                    [
                        'harvest_id' => $harvest->id,
                        'description' => 'Ikan lele',
                        'quantity' => 21,
                        'unit_price' => 30000,
                    ],
                ],
            ])
        );

        $response
            ->assertStatus(500)
            ->assertJsonFragment([
                'message' =>
                'Berat yang dijual melebihi hasil panen yang tersedia. Tersedia: 20 kg.',
            ]);

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
    }

    public function test_sale_cannot_exceed_remaining_harvest_weight_after_previous_sale(): void
    {
        $harvest = $this->createHarvest(100);

        $firstResponse = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'invoice_number' => 'INV-FIRST-001',
                'paid_amount' => 300000,
                'items' => [
                    [
                        'harvest_id' => $harvest->id,
                        'description' => 'Penjualan pertama',
                        'quantity' => 70,
                        'unit_price' => 30000,
                    ],
                ],
            ])
        );

        $firstResponse->assertCreated();

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'invoice_number' => 'INV-SECOND-001',
                'items' => [
                    [
                        'harvest_id' => $harvest->id,
                        'description' => 'Penjualan kedua',
                        'quantity' => 31,
                        'unit_price' => 30000,
                    ],
                ],
            ])
        );

        $response
            ->assertStatus(500)
            ->assertJsonFragment([
                'message' =>
                'Berat yang dijual melebihi hasil panen yang tersedia. Tersedia: 30 kg.',
            ]);

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
    }

    public function test_paid_sale_creates_cash_in_transaction(): void
    {
        $harvest = $this->createHarvest();

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'invoice_number' => 'INV-CASH-001',
                'paid_amount' => 250000,
            ])
        );

        $response->assertCreated();

        $saleId = $response->json('data.id');

        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'in',
            'category' => 'sale',
            'reference_type' => Sale::class,
            'reference_id' => $saleId,
            'description' => 'Pembayaran penjualan INV-CASH-001',
            'amount' => 250000,
            'transaction_date' => '2026-09-30 00:00:00',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_unpaid_sale_does_not_create_cash_transaction(): void
    {
        $harvest = $this->createHarvest();

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'invoice_number' => 'INV-UNPAID-001',
                'paid_amount' => 0,
            ])
        );

        $response->assertCreated();

        $this->assertDatabaseCount('cash_transactions', 0);
    }

    public function test_duplicate_invoice_number_is_rejected(): void
    {
        $harvest = $this->createHarvest();

        $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'invoice_number' => 'INV-DUPLICATE-001',
            ])
        )->assertCreated();

        $secondHarvest = $this->createHarvest();

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($secondHarvest, [
                'invoice_number' => 'INV-DUPLICATE-001',
            ])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'invoice_number',
            ]);
    }

    public function test_sale_requires_valid_data(): void
    {
        $response = $this->postJson('/api/sales', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'invoice_number',
                'sale_date',
                'paid_amount',
                'items',
            ]);
    }

    public function test_sale_requires_at_least_one_item(): void
    {
        $response = $this->postJson(
            '/api/sales',
            [
                'invoice_number' => 'INV-NO-ITEM-001',
                'sale_date' => '2026-09-30',
                'paid_amount' => 0,
                'items' => [],
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items',
            ]);
    }

    public function test_sale_item_quantity_must_be_greater_than_zero(): void
    {
        $harvest = $this->createHarvest();

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'items' => [
                    [
                        'harvest_id' => $harvest->id,
                        'description' => 'Ikan lele',
                        'quantity' => 0,
                        'unit_price' => 30000,
                    ],
                ],
            ])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.quantity',
            ]);
    }

    public function test_sale_item_unit_price_cannot_be_negative(): void
    {
        $harvest = $this->createHarvest();

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'items' => [
                    [
                        'harvest_id' => $harvest->id,
                        'description' => 'Ikan lele',
                        'quantity' => 10,
                        'unit_price' => -1,
                    ],
                ],
            ])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.unit_price',
            ]);
    }

    public function test_can_show_sale(): void
    {
        $harvest = $this->createHarvest();

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'invoice_number' => 'INV-SHOW-001',
                'paid_amount' => 100000,
            ])
        );

        $response->assertCreated();

        $saleId = $response->json('data.id');

        $showResponse = $this->getJson(
            "/api/sales/{$saleId}"
        );

        $showResponse
            ->assertOk()
            ->assertJsonPath('data.id', $saleId)
            ->assertJsonPath('data.invoice_number', 'INV-SHOW-001')
            ->assertJsonPath('data.items.0.harvest.id', $harvest->id)
            ->assertJsonPath('data.items.0.harvest.harvest_date', '2026-09-29');
    }

    public function test_update_sale_is_disabled(): void
    {
        $harvest = $this->createHarvest();

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'invoice_number' => 'INV-UPDATE-001',
            ])
        );

        $response->assertCreated();

        $saleId = $response->json('data.id');

        $updateResponse = $this->putJson(
            "/api/sales/{$saleId}",
            $this->salePayload($harvest)
        );

        $updateResponse
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' =>
                'Update sale akan diaktifkan setelah mekanisme koreksi stok dan transaksi keuangan selesai.',
            ]);
    }

    public function test_delete_sale_is_disabled(): void
    {
        $harvest = $this->createHarvest();

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($harvest, [
                'invoice_number' => 'INV-DELETE-001',
            ])
        );

        $response->assertCreated();

        $saleId = $response->json('data.id');

        $deleteResponse = $this->deleteJson(
            "/api/sales/{$saleId}"
        );

        $deleteResponse
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' =>
                'Penghapusan sale akan diaktifkan setelah mekanisme koreksi stok dan transaksi keuangan selesai.',
            ]);

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
        ]);
    }

    public function test_show_returns_404_for_non_existing_sale(): void
    {
        $response = $this->getJson('/api/sales/999999');

        $response->assertNotFound();
    }
}
