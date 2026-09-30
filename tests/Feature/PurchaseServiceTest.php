<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\StockMovement;
use App\Models\CashTransaction;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_with_partial_payment_creates_purchase_stock_and_cash_expense(): void
    {
        $category = ProductCategory::create([
            'name' => 'Pakan',
            'code' => 'PAKAN',
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-001',
            'name' => 'Supplier Test',
            'phone' => '08123456789',
            'address' => 'Bandung',
            'notes' => null,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PRD-001',
            'name' => 'Pelet Lele',
            'unit' => 'kg',
            'current_stock' => 10,
            'minimum_stock' => 5,
            'average_price' => 12000,
            'is_active' => true,
        ]);

        $service = app(PurchaseService::class);

        $purchase = $service->create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PUR-TEST-001',
            'purchase_date' => '2026-09-30',
            'discount' => 50000,
            'additional_cost' => 25000,
            'payment_status' => 'partial',
            'paid_amount' => 100000,
            'notes' => 'Test pembelian',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_price' => 15000,
                ],
            ],
        ]);

        /*
         * Subtotal:
         * 10 x 15.000 = 150.000
         *
         * Total:
         * 150.000 - 50.000 + 25.000 = 125.000
         */
        $this->assertEquals(150000, (float) $purchase->subtotal);
        $this->assertEquals(50000, (float) $purchase->discount);
        $this->assertEquals(25000, (float) $purchase->additional_cost);
        $this->assertEquals(125000, (float) $purchase->total);

        $this->assertEquals(100000, (float) $purchase->paid_amount);
        $this->assertEquals('partial', $purchase->payment_status);

        /*
         * Stok:
         * 10 + 10 = 20 kg
         */
        $this->assertEquals(
            20,
            (float) $product->fresh()->current_stock
        );

        /*
         * Purchase tersimpan.
         */
        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'invoice_number' => 'PUR-TEST-001',
            'payment_status' => 'partial',
        ]);

        /*
         * Purchase item tersimpan.
         */
        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 15000,
            'subtotal' => 150000,
        ]);

        /*
         * Stock movement tercatat sebagai IN.
         */
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 10,
            'unit_price' => 15000,
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
        ]);

        /*
         * Kas keluar hanya sebesar paid_amount.
         */
        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'out',
            'category' => 'purchase',
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'amount' => 100000,
        ]);

        $cashTransaction = CashTransaction::where([
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
        ])->first();

        $this->assertNotNull($cashTransaction);

        $this->assertEquals(
            '2026-09-30',
            $cashTransaction->transaction_date->format('Y-m-d')
        );
    }

    public function test_unpaid_purchase_does_not_create_cash_expense(): void
    {
        $category = ProductCategory::create([
            'name' => 'Pakan',
            'code' => 'PAKAN-UNPAID',
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-UNPAID',
            'name' => 'Supplier Unpaid',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PRD-UNPAID',
            'name' => 'Pelet Lele',
            'unit' => 'kg',
            'current_stock' => 10,
            'minimum_stock' => 5,
            'average_price' => 12000,
            'is_active' => true,
        ]);

        $purchase = app(PurchaseService::class)->create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PUR-TEST-UNPAID',
            'purchase_date' => '2026-09-30',
            'discount' => 0,
            'additional_cost' => 0,
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_price' => 15000,
                ],
            ],
        ]);

        $this->assertEquals('unpaid', $purchase->payment_status);
        $this->assertEquals(0, (float) $purchase->paid_amount);

        $this->assertDatabaseMissing('cash_transactions', [
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
        ]);
    }

    public function test_paid_purchase_creates_cash_expense_equal_to_total(): void
    {
        $category = ProductCategory::create([
            'name' => 'Obat',
            'code' => 'OBAT-PAID',
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-PAID',
            'name' => 'Supplier Paid',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PRD-PAID',
            'name' => 'Vitamin Lele',
            'unit' => 'pcs',
            'current_stock' => 0,
            'minimum_stock' => 0,
            'average_price' => 0,
            'is_active' => true,
        ]);

        $purchase = app(PurchaseService::class)->create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PUR-TEST-PAID',
            'purchase_date' => '2026-09-29',
            'discount' => 0,
            'additional_cost' => 10000,
            'payment_status' => 'paid',
            'paid_amount' => 160000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_price' => 15000,
                ],
            ],
        ]);

        $this->assertEquals(160000, (float) $purchase->total);
        $this->assertEquals(160000, (float) $purchase->paid_amount);
        $this->assertEquals('paid', $purchase->payment_status);

        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'out',
            'category' => 'purchase',
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'amount' => 160000,
        ]);

        $cashTransaction = CashTransaction::where([
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
        ])->first();

        $this->assertNotNull($cashTransaction);

        $this->assertEquals(
            '2026-09-29',
            $cashTransaction->transaction_date->format('Y-m-d')
        );
    }

    public function test_purchase_rejects_payment_greater_than_total(): void
    {
        $category = ProductCategory::create([
            'name' => 'Pakan',
            'code' => 'PAKAN-OVERPAY',
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-OVERPAY',
            'name' => 'Supplier Overpay',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PRD-OVERPAY',
            'name' => 'Pelet Lele',
            'unit' => 'kg',
            'current_stock' => 10,
            'minimum_stock' => 5,
            'average_price' => 12000,
            'is_active' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Jumlah pembayaran tidak boleh melebihi total pembelian.'
        );

        app(PurchaseService::class)->create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PUR-TEST-OVERPAY',
            'purchase_date' => '2026-09-30',
            'payment_status' => 'paid',
            'paid_amount' => 200000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_price' => 15000,
                ],
            ],
        ]);
    }

    public function test_purchase_rejects_negative_payment(): void
    {
        $category = ProductCategory::create([
            'name' => 'Pakan',
            'code' => 'PAKAN-NEGATIVE',
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-NEGATIVE',
            'name' => 'Supplier Negative',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PRD-NEGATIVE',
            'name' => 'Pelet Lele',
            'unit' => 'kg',
            'current_stock' => 10,
            'minimum_stock' => 5,
            'average_price' => 12000,
            'is_active' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Jumlah pembayaran tidak boleh negatif.'
        );

        app(PurchaseService::class)->create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PUR-TEST-NEGATIVE',
            'purchase_date' => '2026-09-30',
            'payment_status' => 'unpaid',
            'paid_amount' => -10000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_price' => 15000,
                ],
            ],
        ]);
    }

    public function test_unpaid_purchase_rejects_non_zero_payment(): void
    {
        $category = ProductCategory::create([
            'name' => 'Pakan',
            'code' => 'PAKAN-UNPAID-INVALID',
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-UNPAID-INVALID',
            'name' => 'Supplier Unpaid Invalid',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PRD-UNPAID-INVALID',
            'name' => 'Pelet Lele',
            'unit' => 'kg',
            'current_stock' => 10,
            'minimum_stock' => 5,
            'average_price' => 12000,
            'is_active' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Pembelian dengan status unpaid harus memiliki paid_amount 0.'
        );

        app(PurchaseService::class)->create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PUR-TEST-UNPAID-INVALID',
            'purchase_date' => '2026-09-30',
            'payment_status' => 'unpaid',
            'paid_amount' => 50000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_price' => 15000,
                ],
            ],
        ]);
    }

    public function test_partial_purchase_rejects_zero_payment(): void
    {
        $category = ProductCategory::create([
            'name' => 'Pakan',
            'code' => 'PAKAN-PARTIAL-INVALID',
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-PARTIAL-INVALID',
            'name' => 'Supplier Partial Invalid',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PRD-PARTIAL-INVALID',
            'name' => 'Pelet Lele',
            'unit' => 'kg',
            'current_stock' => 10,
            'minimum_stock' => 5,
            'average_price' => 12000,
            'is_active' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Pembelian dengan status partial harus memiliki paid_amount lebih dari 0 dan kurang dari total.'
        );

        app(PurchaseService::class)->create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PUR-TEST-PARTIAL-INVALID',
            'purchase_date' => '2026-09-30',
            'payment_status' => 'partial',
            'paid_amount' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_price' => 15000,
                ],
            ],
        ]);
    }

    public function test_partial_purchase_rejects_payment_equal_to_total(): void
    {
        $category = ProductCategory::create([
            'name' => 'Pakan',
            'code' => 'PAKAN-PARTIAL-TOTAL',
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-PARTIAL-TOTAL',
            'name' => 'Supplier Partial Total',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PRD-PARTIAL-TOTAL',
            'name' => 'Pelet Lele',
            'unit' => 'kg',
            'current_stock' => 10,
            'minimum_stock' => 5,
            'average_price' => 12000,
            'is_active' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Pembelian dengan status partial harus memiliki paid_amount lebih dari 0 dan kurang dari total.'
        );

        app(PurchaseService::class)->create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PUR-TEST-PARTIAL-TOTAL',
            'purchase_date' => '2026-09-30',
            'payment_status' => 'partial',
            'paid_amount' => 150000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_price' => 15000,
                ],
            ],
        ]);
    }

    public function test_paid_purchase_rejects_payment_less_than_total(): void
    {
        $category = ProductCategory::create([
            'name' => 'Obat',
            'code' => 'OBAT-PAID-INVALID',
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-PAID-INVALID',
            'name' => 'Supplier Paid Invalid',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PRD-PAID-INVALID',
            'name' => 'Vitamin Lele',
            'unit' => 'pcs',
            'current_stock' => 0,
            'minimum_stock' => 0,
            'average_price' => 0,
            'is_active' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Pembelian dengan status paid harus memiliki paid_amount sama dengan total.'
        );

        app(PurchaseService::class)->create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PUR-TEST-PAID-INVALID',
            'purchase_date' => '2026-09-30',
            'payment_status' => 'paid',
            'paid_amount' => 100000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_price' => 15000,
                ],
            ],
        ]);
    }

    public function test_purchase_rejects_invalid_payment_status(): void
    {
        $category = ProductCategory::create([
            'name' => 'Pakan',
            'code' => 'PAKAN-STATUS-INVALID',
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-STATUS-INVALID',
            'name' => 'Supplier Status Invalid',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PRD-STATUS-INVALID',
            'name' => 'Pelet Lele',
            'unit' => 'kg',
            'current_stock' => 10,
            'minimum_stock' => 5,
            'average_price' => 12000,
            'is_active' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Status pembayaran tidak valid.'
        );

        app(PurchaseService::class)->create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PUR-TEST-STATUS-INVALID',
            'purchase_date' => '2026-09-30',
            'payment_status' => 'invalid',
            'paid_amount' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_price' => 15000,
                ],
            ],
        ]);
    }

    public function test_purchase_rejects_negative_total(): void
    {
        $category = ProductCategory::create([
            'name' => 'Pakan',
            'code' => 'PAKAN-NEGATIVE-TOTAL',
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-NEGATIVE-TOTAL',
            'name' => 'Supplier Negative Total',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PRD-NEGATIVE-TOTAL',
            'name' => 'Pelet Lele',
            'unit' => 'kg',
            'current_stock' => 10,
            'minimum_stock' => 5,
            'average_price' => 12000,
            'is_active' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Total pembelian tidak boleh negatif.'
        );

        app(PurchaseService::class)->create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PUR-TEST-NEGATIVE-TOTAL',
            'purchase_date' => '2026-09-30',
            'discount' => 200000,
            'additional_cost' => 0,
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_price' => 15000,
                ],
            ],
        ]);
    }
}
