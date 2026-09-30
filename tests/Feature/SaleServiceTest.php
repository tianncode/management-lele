<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\FishCycle;
use App\Models\Harvest;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use RuntimeException;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createHarvest(
        float $totalWeight = 100
    ): Harvest {

        $fishCycle = FishCycle::create([
            'pond_id' => $this->createPond()->id,
            'code' => 'CYCLE-001',
            'start_date' => '2026-01-01',
            'initial_fish_count' => 1000,
            'status' => 'active',
        ]);

        return Harvest::create([
            'fish_cycle_id' => $fishCycle->id,
            'harvest_date' => '2026-09-30',
            'total_fish' => 1000,
            'total_weight' => $totalWeight,
            'average_weight' => 0.10,
            'selling_price_per_kg' => 15000,
            'estimated_revenue' => $totalWeight * 15000,
        ]);
    }

    private function createPond(): \App\Models\Pond
    {
        return \App\Models\Pond::create([
            'code' => 'POND-' . uniqid(),
            'name' => 'Kolam Test',
            'length' => 10,
            'width' => 5,
            'depth' => 1.5,
            'volume' => 75,
            'status' => 'cultivation',
        ]);
    }

    public function test_sale_with_partial_payment_creates_sale_and_cash_income(): void
    {
        $customer = Customer::create([
            'code' => 'CUS-001',
            'name' => 'Customer Test',
        ]);

        $harvest = $this->createHarvest();

        $sale = app(SaleService::class)->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-001',
            'sale_date' => '2026-09-30',
            'discount' => 0,
            'paid_amount' => 1000000,
            'notes' => null,
            'items' => [
                [
                    'harvest_id' => $harvest->id,
                    'description' => 'Ikan lele',
                    'quantity' => 100,
                    'unit_price' => 15000,
                ],
            ],
        ]);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'invoice_number' => 'INV-TEST-001',
            'subtotal' => 1500000,
            'total' => 1500000,
            'paid_amount' => 1000000,
            'payment_status' => 'partial',
        ]);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'harvest_id' => $harvest->id,
            'quantity' => 100,
            'unit_price' => 15000,
            'subtotal' => 1500000,
        ]);

        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'in',
            'category' => 'sale',
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'amount' => 1000000,
        ]);
    }

    public function test_unpaid_sale_does_not_create_cash_income(): void
    {
        $customer = Customer::create([
            'code' => 'CUS-002',
            'name' => 'Customer Unpaid',
        ]);

        $harvest = $this->createHarvest();

        $sale = app(SaleService::class)->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-002',
            'sale_date' => '2026-09-30',
            'discount' => 0,
            'paid_amount' => 0,
            'notes' => null,
            'items' => [
                [
                    'harvest_id' => $harvest->id,
                    'description' => 'Ikan lele',
                    'quantity' => 50,
                    'unit_price' => 15000,
                ],
            ],
        ]);

        $this->assertEquals('unpaid', $sale->payment_status);
        $this->assertEquals(0, (float) $sale->paid_amount);

        $this->assertDatabaseMissing('cash_transactions', [
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
        ]);
    }

    public function test_paid_sale_creates_cash_income_equal_to_total(): void
    {
        $customer = Customer::create([
            'code' => 'CUS-003',
            'name' => 'Customer Paid',
        ]);

        $harvest = $this->createHarvest();

        $sale = app(SaleService::class)->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-003',
            'sale_date' => '2026-09-30',
            'discount' => 0,
            'paid_amount' => 750000,
            'notes' => null,
            'items' => [
                [
                    'harvest_id' => $harvest->id,
                    'description' => 'Ikan lele',
                    'quantity' => 50,
                    'unit_price' => 15000,
                ],
            ],
        ]);

        $this->assertEquals(750000, (float) $sale->total);
        $this->assertEquals(750000, (float) $sale->paid_amount);
        $this->assertEquals('paid', $sale->payment_status);

        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'in',
            'category' => 'sale',
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'amount' => 750000,
        ]);
    }

    public function test_payment_cannot_exceed_sale_total(): void
    {
        $customer = Customer::create([
            'code' => 'CUS-004',
            'name' => 'Customer Overpayment',
        ]);

        $harvest = $this->createHarvest();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Jumlah pembayaran tidak boleh melebihi total penjualan.'
        );

        app(SaleService::class)->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-004',
            'sale_date' => '2026-09-30',
            'discount' => 0,
            'paid_amount' => 800000,
            'notes' => null,
            'items' => [
                [
                    'harvest_id' => $harvest->id,
                    'description' => 'Ikan lele',
                    'quantity' => 50,
                    'unit_price' => 15000,
                ],
            ],
        ]);
    }

    public function test_cannot_sell_more_than_available_harvest_weight(): void
    {
        $customer = Customer::create([
            'code' => 'CUS-005',
            'name' => 'Customer Stock',
        ]);

        $harvest = $this->createHarvest(100);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Berat yang dijual melebihi hasil panen yang tersedia.'
        );

        app(SaleService::class)->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-005',
            'sale_date' => '2026-09-30',
            'discount' => 0,
            'paid_amount' => 0,
            'notes' => null,
            'items' => [
                [
                    'harvest_id' => $harvest->id,
                    'description' => 'Ikan lele',
                    'quantity' => 101,
                    'unit_price' => 15000,
                ],
            ],
        ]);

        $this->assertDatabaseMissing('sales', [
            'invoice_number' => 'INV-TEST-005',
        ]);
    }

    public function test_discount_cannot_make_sale_total_negative(): void
    {
        $customer = Customer::create([
            'code' => 'CUS-006',
            'name' => 'Customer Discount',
        ]);

        $harvest = $this->createHarvest();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Total penjualan tidak boleh negatif.'
        );

        app(SaleService::class)->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-006',
            'sale_date' => '2026-09-30',
            'discount' => 1000000,
            'paid_amount' => 0,
            'notes' => null,
            'items' => [
                [
                    'harvest_id' => $harvest->id,
                    'description' => 'Ikan lele',
                    'quantity' => 10,
                    'unit_price' => 15000,
                ],
            ],
        ]);
    }
}
