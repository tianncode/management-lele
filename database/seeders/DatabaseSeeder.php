<?php

namespace Database\Seeders;

use App\Models\CapitalTransaction;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Feeding;
use App\Models\FishCycle;
use App\Models\Harvest;
use App\Models\Mortality;
use App\Models\OtherIncome;
use App\Models\Pond;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Sampling;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | USERS
        |--------------------------------------------------------------------------
        */

        $admin = User::create([
            'name' => 'Admin Peternakan',
            'email' => 'admin@lele.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $staff = User::create([
            'name' => 'Staff Budidaya',
            'email' => 'staff@lele.test',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | SUPPLIERS
        |--------------------------------------------------------------------------
        */

        $supplierPakan = Supplier::create([
            'code' => 'SUP-001',
            'name' => 'CV Sumber Pakan Jaya',
            'phone' => '081234567890',
            'address' => 'Bandung, Jawa Barat',
            'notes' => 'Supplier utama pakan ikan.',
        ]);

        $supplierBenih = Supplier::create([
            'code' => 'SUP-002',
            'name' => 'UD Benih Lele Sejahtera',
            'phone' => '081298765432',
            'address' => 'Cimahi, Jawa Barat',
            'notes' => 'Supplier benih lele.',
        ]);

        $supplierObat = Supplier::create([
            'code' => 'SUP-003',
            'name' => 'Toko Mina Makmur',
            'phone' => '082112223333',
            'address' => 'Kabupaten Bandung, Jawa Barat',
            'notes' => 'Vitamin, obat dan kebutuhan budidaya.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | CUSTOMERS
        |--------------------------------------------------------------------------
        */

        $customerPasar = Customer::create([
            'code' => 'CUS-001',
            'name' => 'Pasar Ikan Ciroyom',
            'phone' => '081311112222',
            'address' => 'Bandung, Jawa Barat',
            'notes' => 'Pembeli ikan konsumsi.',
        ]);

        $customerWarung = Customer::create([
            'code' => 'CUS-002',
            'name' => 'Warung Pecel Lele Barokah',
            'phone' => '082233334444',
            'address' => 'Bandung, Jawa Barat',
            'notes' => 'Pelanggan rutin.',
        ]);

        $customerRestoran = Customer::create([
            'code' => 'CUS-003',
            'name' => 'RM Lele Sambal Nusantara',
            'phone' => '083344445555',
            'address' => 'Cimahi, Jawa Barat',
            'notes' => 'Pembelian lele ukuran konsumsi.',
        ]);

        $customerAgen = Customer::create([
            'code' => 'CUS-004',
            'name' => 'Agen Lele Mandiri',
            'phone' => '084455556666',
            'address' => 'Rancaekek, Jawa Barat',
            'notes' => 'Pembelian dalam jumlah besar.',
        ]);

        $customerLain = Customer::create([
            'code' => 'CUS-005',
            'name' => 'Pembeli Umum',
            'phone' => null,
            'address' => null,
            'notes' => 'Pelanggan retail.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | PRODUCT CATEGORIES
        |--------------------------------------------------------------------------
        */

        $catBenih = ProductCategory::create([
            'name' => 'Benih',
            'description' => 'Benih ikan lele untuk kebutuhan budidaya.',
            'is_active' => true,
        ]);

        $catPakan = ProductCategory::create([
            'name' => 'Pakan',
            'description' => 'Pakan ikan berdasarkan ukuran dan kebutuhan pertumbuhan.',
            'is_active' => true,
        ]);

        $catVitamin = ProductCategory::create([
            'name' => 'Vitamin',
            'description' => 'Vitamin dan suplemen untuk ikan.',
            'is_active' => true,
        ]);

        $catObat = ProductCategory::create([
            'name' => 'Obat',
            'description' => 'Obat dan perlengkapan penanganan penyakit ikan.',
            'is_active' => true,
        ]);

        $catPeralatan = ProductCategory::create([
            'name' => 'Peralatan',
            'description' => 'Peralatan operasional budidaya.',
            'is_active' => true,
        ]);

        $catLainnya = ProductCategory::create([
            'name' => 'Lainnya',
            'description' => 'Barang pendukung lainnya.',
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | PRODUCTS
        |--------------------------------------------------------------------------
        */

        $benih = Product::create([
            'category_id' => $catBenih->id,
            'code' => 'BEN-001',
            'name' => 'Benih Lele 5-7 cm',
            'unit' => 'ekor',
            'current_stock' => 0,
            'minimum_stock' => 5000,
            'average_price' => 150,
            'is_active' => true,
        ]);

        $pakanStarter = Product::create([
            'category_id' => $catPakan->id,
            'code' => 'PAK-001',
            'name' => 'Pakan Starter PF-500',
            'unit' => 'kg',
            'current_stock' => 0,
            'minimum_stock' => 10,
            'average_price' => 12500,
            'is_active' => true,
        ]);

        $pakanGrower = Product::create([
            'category_id' => $catPakan->id,
            'code' => 'PAK-002',
            'name' => 'Pakan Grower PF-1000',
            'unit' => 'kg',
            'current_stock' => 0,
            'minimum_stock' => 20,
            'average_price' => 11500,
            'is_active' => true,
        ]);

        $pakanFinisher = Product::create([
            'category_id' => $catPakan->id,
            'code' => 'PAK-003',
            'name' => 'Pakan Finisher',
            'unit' => 'kg',
            'current_stock' => 0,
            'minimum_stock' => 20,
            'average_price' => 11000,
            'is_active' => true,
        ]);

        $vitamin = Product::create([
            'category_id' => $catVitamin->id,
            'code' => 'VIT-001',
            'name' => 'Vitamin Ikan',
            'unit' => 'botol',
            'current_stock' => 0,
            'minimum_stock' => 2,
            'average_price' => 35000,
            'is_active' => true,
        ]);

        $probiotik = Product::create([
            'category_id' => $catVitamin->id,
            'code' => 'VIT-002',
            'name' => 'Probiotik Kolam',
            'unit' => 'botol',
            'current_stock' => 0,
            'minimum_stock' => 2,
            'average_price' => 45000,
            'is_active' => true,
        ]);

        $obat = Product::create([
            'category_id' => $catObat->id,
            'code' => 'OBT-001',
            'name' => 'Obat Ikan Umum',
            'unit' => 'botol',
            'current_stock' => 0,
            'minimum_stock' => 2,
            'average_price' => 40000,
            'is_active' => true,
        ]);

        $jaring = Product::create([
            'category_id' => $catPeralatan->id,
            'code' => 'ALT-001',
            'name' => 'Jaring Panen',
            'unit' => 'pcs',
            'current_stock' => 0,
            'minimum_stock' => 1,
            'average_price' => 175000,
            'is_active' => true,
        ]);

        $ember = Product::create([
            'category_id' => $catPeralatan->id,
            'code' => 'ALT-002',
            'name' => 'Ember Budidaya',
            'unit' => 'pcs',
            'current_stock' => 0,
            'minimum_stock' => 2,
            'average_price' => 50000,
            'is_active' => true,
        ]);

        $garam = Product::create([
            'category_id' => $catLainnya->id,
            'code' => 'LNN-001',
            'name' => 'Garam Kolam',
            'unit' => 'kg',
            'current_stock' => 0,
            'minimum_stock' => 5,
            'average_price' => 6000,
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | PONDS
        |--------------------------------------------------------------------------
        */

        $pondA = Pond::create([
            'code' => 'KLM-001',
            'name' => 'Kolam A',
            'length' => 8,
            'width' => 5,
            'depth' => 1.2,
            'volume' => 48,
            'status' => 'cultivation',
            'notes' => 'Kolam utama budidaya.',
        ]);

        $pondB = Pond::create([
            'code' => 'KLM-002',
            'name' => 'Kolam B',
            'length' => 8,
            'width' => 4,
            'depth' => 1.1,
            'volume' => 35.2,
            'status' => 'cultivation',
            'notes' => 'Kolam pembesaran.',
        ]);

        $pondC = Pond::create([
            'code' => 'KLM-003',
            'name' => 'Kolam C',
            'length' => 6,
            'width' => 4,
            'depth' => 1,
            'volume' => 24,
            'status' => 'harvest',
            'notes' => 'Kolam yang memasuki masa panen.',
        ]);

        $pondD = Pond::create([
            'code' => 'KLM-004',
            'name' => 'Kolam D',
            'length' => 6,
            'width' => 4,
            'depth' => 1,
            'volume' => 24,
            'status' => 'available',
            'notes' => 'Siap untuk siklus berikutnya.',
        ]);

        $pondE = Pond::create([
            'code' => 'KLM-005',
            'name' => 'Kolam E',
            'length' => 5,
            'width' => 3,
            'depth' => 1,
            'volume' => 15,
            'status' => 'maintenance',
            'notes' => 'Sedang dilakukan perawatan dasar.',
        ]);

        $pondF = Pond::create([
            'code' => 'KLM-006',
            'name' => 'Kolam F',
            'length' => 5,
            'width' => 3,
            'depth' => 1,
            'volume' => 15,
            'status' => 'preparation',
            'notes' => 'Persiapan untuk siklus baru.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | EXPENSE CATEGORIES
        |--------------------------------------------------------------------------
        */

        $expenseElectricity = ExpenseCategory::create([
            'name' => 'Listrik',
            'description' => 'Biaya listrik untuk pompa dan operasional.',
            'is_active' => true,
        ]);

        $expenseWater = ExpenseCategory::create([
            'name' => 'Air',
            'description' => 'Biaya pengisian dan pengelolaan air.',
            'is_active' => true,
        ]);

        $expenseTransport = ExpenseCategory::create([
            'name' => 'Transportasi',
            'description' => 'Biaya transportasi operasional.',
            'is_active' => true,
        ]);

        $expenseMaintenance = ExpenseCategory::create([
            'name' => 'Perawatan',
            'description' => 'Perawatan kolam dan peralatan.',
            'is_active' => true,
        ]);

        $expenseLabor = ExpenseCategory::create([
            'name' => 'Tenaga Kerja',
            'description' => 'Biaya tenaga kerja.',
            'is_active' => true,
        ]);

        $expenseOperational = ExpenseCategory::create([
            'name' => 'Operasional',
            'description' => 'Biaya operasional lainnya.',
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | CAPITAL
        |--------------------------------------------------------------------------
        */

        $capital = CapitalTransaction::create([
            'transaction_date' => now()->subDays(30)->toDateString(),
            'type' => 'in',
            'amount' => 25000000,
            'description' => 'Modal awal usaha budidaya lele',
            'notes' => 'Modal awal periode budidaya.',
            'created_by' => $admin->id,
        ]);

        CashTransaction::create([
            'transaction_date' => $capital->transaction_date,
            'type' => 'in',
            'category' => 'capital',
            'reference_type' => 'capital_transaction',
            'reference_id' => $capital->id,
            'description' => $capital->description,
            'amount' => $capital->amount,
            'created_by' => $admin->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | PURCHASES
        |--------------------------------------------------------------------------
        */

        $purchase1 = Purchase::create([
            'supplier_id' => $supplierBenih->id,
            'invoice_number' => 'PB-2026-0001',
            'purchase_date' => now()->subDays(25)->toDateString(),
            'subtotal' => 2250000,
            'discount' => 0,
            'additional_cost' => 50000,
            'total' => 2300000,
            'payment_status' => 'paid',
            'notes' => 'Pembelian benih untuk siklus budidaya.',
            'created_by' => $admin->id,
        ]);

        $purchaseItem1 = PurchaseItem::create([
            'purchase_id' => $purchase1->id,
            'product_id' => $benih->id,
            'quantity' => 15000,
            'unit_price' => 150,
            'subtotal' => 2250000,
        ]);

        StockMovement::create([
            'product_id' => $benih->id,
            'type' => 'in',
            'quantity' => 15000,
            'unit_price' => 150,
            'total_value' => 2250000,
            'reference_type' => 'purchase',
            'reference_id' => $purchase1->id,
            'movement_date' => $purchase1->purchase_date,
            'notes' => 'Stok benih dari pembelian PB-2026-0001.',
            'created_by' => $admin->id,
        ]);

        $purchase2 = Purchase::create([
            'supplier_id' => $supplierPakan->id,
            'invoice_number' => 'PB-2026-0002',
            'purchase_date' => now()->subDays(20)->toDateString(),
            'subtotal' => 2875000,
            'discount' => 75000,
            'additional_cost' => 0,
            'total' => 2800000,
            'payment_status' => 'paid',
            'notes' => 'Stok pakan awal.',
            'created_by' => $admin->id,
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase2->id,
            'product_id' => $pakanStarter->id,
            'quantity' => 50,
            'unit_price' => 12500,
            'subtotal' => 625000,
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase2->id,
            'product_id' => $pakanGrower->id,
            'quantity' => 100,
            'unit_price' => 11500,
            'subtotal' => 1150000,
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase2->id,
            'product_id' => $pakanFinisher->id,
            'quantity' => 100,
            'unit_price' => 11000,
            'subtotal' => 1100000,
        ]);

        foreach (
            [
                [$pakanStarter, 50, 12500, 625000],
                [$pakanGrower, 100, 11500, 1150000],
                [$pakanFinisher, 100, 11000, 1100000],
            ] as [$product, $quantity, $price, $value]
        ) {
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $quantity,
                'unit_price' => $price,
                'total_value' => $value,
                'reference_type' => 'purchase',
                'reference_id' => $purchase2->id,
                'movement_date' => $purchase2->purchase_date,
                'notes' => 'Stok awal pakan.',
                'created_by' => $admin->id,
            ]);
        }

        $purchase3 = Purchase::create([
            'supplier_id' => $supplierObat->id,
            'invoice_number' => 'PB-2026-0003',
            'purchase_date' => now()->subDays(18)->toDateString(),
            'subtotal' => 455000,
            'discount' => 5000,
            'additional_cost' => 0,
            'total' => 450000,
            'payment_status' => 'paid',
            'notes' => 'Vitamin dan obat budidaya.',
            'created_by' => $staff->id,
        ]);

        foreach (
            [
                [$vitamin, 5, 35000, 175000],
                [$probiotik, 4, 45000, 180000],
                [$obat, 1, 40000, 40000],
                [$garam, 10, 6000, 60000],
            ] as [$product, $quantity, $price, $value]
        ) {
            PurchaseItem::create([
                'purchase_id' => $purchase3->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $price,
                'subtotal' => $value,
            ]);

            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $quantity,
                'unit_price' => $price,
                'total_value' => $value,
                'reference_type' => 'purchase',
                'reference_id' => $purchase3->id,
                'movement_date' => $purchase3->purchase_date,
                'notes' => 'Stok vitamin, obat dan kebutuhan kolam.',
                'created_by' => $staff->id,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE STOCK
        |--------------------------------------------------------------------------
        */

        $benih->update([
            'current_stock' => 15000,
        ]);

        $pakanStarter->update([
            'current_stock' => 50,
        ]);

        $pakanGrower->update([
            'current_stock' => 100,
        ]);

        $pakanFinisher->update([
            'current_stock' => 100,
        ]);

        $vitamin->update([
            'current_stock' => 5,
        ]);

        $probiotik->update([
            'current_stock' => 4,
        ]);

        $obat->update([
            'current_stock' => 1,
        ]);

        $garam->update([
            'current_stock' => 10,
        ]);

        /*
        |--------------------------------------------------------------------------
        | FISH CYCLES
        |--------------------------------------------------------------------------
        */

        $cycleA = FishCycle::create([
            'pond_id' => $pondA->id,
            'seed_product_id' => $benih->id,
            'code' => 'CYC-2026-001',
            'start_date' => now()->subDays(24)->toDateString(),
            'target_harvest_date' => now()->addDays(45)->toDateString(),
            'initial_fish_count' => 7000,
            'seed_size' => 5.5,
            'seed_unit_price' => 150,
            'status' => 'active',
            'notes' => 'Siklus pembesaran utama.',
        ]);

        $cycleB = FishCycle::create([
            'pond_id' => $pondB->id,
            'seed_product_id' => $benih->id,
            'code' => 'CYC-2026-002',
            'start_date' => now()->subDays(15)->toDateString(),
            'target_harvest_date' => now()->addDays(55)->toDateString(),
            'initial_fish_count' => 5000,
            'seed_size' => 5.5,
            'seed_unit_price' => 150,
            'status' => 'active',
            'notes' => 'Siklus pembesaran tahap kedua.',
        ]);

        $cycleC = FishCycle::create([
            'pond_id' => $pondC->id,
            'seed_product_id' => $benih->id,
            'code' => 'CYC-2026-003',
            'start_date' => now()->subDays(70)->toDateString(),
            'target_harvest_date' => now()->subDays(2)->toDateString(),
            'initial_fish_count' => 3000,
            'seed_size' => 5.5,
            'seed_unit_price' => 150,
            'status' => 'harvested',
            'notes' => 'Siklus yang sudah selesai dipanen.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | MORTALITY
        |--------------------------------------------------------------------------
        */

        Mortality::create([
            'fish_cycle_id' => $cycleA->id,
            'mortality_date' => now()->subDays(18)->toDateString(),
            'quantity' => 80,
            'cause' => 'Adaptasi awal',
            'notes' => 'Mortalitas normal pada fase awal.',
            'created_by' => $staff->id,
        ]);

        Mortality::create([
            'fish_cycle_id' => $cycleA->id,
            'mortality_date' => now()->subDays(8)->toDateString(),
            'quantity' => 45,
            'cause' => 'Kualitas air',
            'notes' => 'Dilakukan pergantian sebagian air.',
            'created_by' => $staff->id,
        ]);

        Mortality::create([
            'fish_cycle_id' => $cycleB->id,
            'mortality_date' => now()->subDays(5)->toDateString(),
            'quantity' => 35,
            'cause' => 'Adaptasi awal',
            'notes' => null,
            'created_by' => $staff->id,
        ]);

        Mortality::create([
            'fish_cycle_id' => $cycleC->id,
            'mortality_date' => now()->subDays(30)->toDateString(),
            'quantity' => 70,
            'cause' => 'Penyakit',
            'notes' => 'Penanganan menggunakan obat ikan.',
            'created_by' => $staff->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | SAMPLINGS
        |--------------------------------------------------------------------------
        */

        Sampling::create([
            'fish_cycle_id' => $cycleA->id,
            'sampling_date' => now()->subDays(3)->toDateString(),
            'sample_count' => 100,
            'average_weight' => 72,
            'average_length' => 16.5,
            'estimated_biomass' => 497.88,
            'estimated_population' => 6875,
            'notes' => 'Pertumbuhan sesuai target.',
            'created_by' => $staff->id,
        ]);

        Sampling::create([
            'fish_cycle_id' => $cycleB->id,
            'sampling_date' => now()->subDays(2)->toDateString(),
            'sample_count' => 100,
            'average_weight' => 55,
            'average_length' => 14.2,
            'estimated_biomass' => 273.08,
            'estimated_population' => 4965,
            'notes' => 'Pertumbuhan cukup stabil.',
            'created_by' => $staff->id,
        ]);

        Sampling::create([
            'fish_cycle_id' => $cycleC->id,
            'sampling_date' => now()->subDays(10)->toDateString(),
            'sample_count' => 100,
            'average_weight' => 105,
            'average_length' => 20.5,
            'estimated_biomass' => 307.65,
            'estimated_population' => 2930,
            'notes' => 'Siap memasuki masa panen.',
            'created_by' => $staff->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | FEEDINGS
        |--------------------------------------------------------------------------
        */

        $feedingData = [
            [$cycleA, $pakanStarter, 12, 12500, 12 * 12500],
            [$cycleA, $pakanStarter, 15, 12500, 15 * 12500],
            [$cycleA, $pakanGrower, 18, 11500, 18 * 11500],
            [$cycleA, $pakanGrower, 20, 11500, 20 * 11500],

            [$cycleB, $pakanStarter, 10, 12500, 10 * 12500],
            [$cycleB, $pakanStarter, 12, 12500, 12 * 12500],
            [$cycleB, $pakanGrower, 15, 11500, 15 * 11500],

            [$cycleC, $pakanGrower, 20, 11500, 20 * 11500],
            [$cycleC, $pakanGrower, 25, 11500, 25 * 11500],
            [$cycleC, $pakanFinisher, 30, 11000, 30 * 11000],
        ];

        foreach ($feedingData as $index => [$cycle, $product, $quantity, $price, $cost]) {
            $feedingDate = now()->subDays(20 - $index)->toDateString();

            $feeding = Feeding::create([
                'fish_cycle_id' => $cycle->id,
                'product_id' => $product->id,
                'feeding_date' => $feedingDate,
                'quantity' => $quantity,
                'unit_price' => $price,
                'total_cost' => $cost,
                'feeding_time' => $index % 2 === 0 ? '08:00' : '16:00',
                'notes' => 'Pemberian pakan rutin.',
                'created_by' => $staff->id,
            ]);

            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => $quantity,
                'unit_price' => $price,
                'total_value' => $cost,
                'reference_type' => 'feeding',
                'reference_id' => $feeding->id,
                'movement_date' => $feedingDate,
                'notes' => 'Pemakaian pakan untuk ' . $cycle->code,
                'created_by' => $staff->id,
            ]);

            $product->decrement('current_stock', $quantity);
        }

        /*
        |--------------------------------------------------------------------------
        | HARVEST
        |--------------------------------------------------------------------------
        */

        $harvest = Harvest::create([
            'fish_cycle_id' => $cycleC->id,
            'harvest_date' => now()->subDays(1)->toDateString(),
            'total_fish' => 2930,
            'total_weight' => 307.65,
            'average_weight' => 105,
            'selling_price_per_kg' => 24500,
            'estimated_revenue' => 307.65 * 24500,
            'notes' => 'Panen parsial dari Kolam C.',
            'created_by' => $staff->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | SALE
        |--------------------------------------------------------------------------
        */

        $sale = Sale::create([
            'customer_id' => $customerAgen->id,
            'invoice_number' => 'PJ-2026-0001',
            'sale_date' => now()->toDateString(),
            'subtotal' => 307.65 * 24500,
            'discount' => 0,
            'total' => 307.65 * 24500,
            'payment_status' => 'paid',
            'notes' => 'Penjualan hasil panen Kolam C.',
            'created_by' => $admin->id,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'harvest_id' => $harvest->id,
            'description' => 'Lele konsumsi hasil panen Kolam C',
            'quantity' => 307.65,
            'unit_price' => 24500,
            'subtotal' => 307.65 * 24500,
        ]);

        CashTransaction::create([
            'transaction_date' => $sale->sale_date,
            'type' => 'in',
            'category' => 'sale',
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
            'description' => 'Penjualan hasil panen ' . $cycleC->code,
            'amount' => $sale->total,
            'created_by' => $admin->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | EXPENSES
        |--------------------------------------------------------------------------
        */

        $expenses = [
            [
                'category' => $expenseElectricity,
                'date' => now()->subDays(10),
                'description' => 'Listrik pompa dan aerasi',
                'amount' => 350000,
            ],
            [
                'category' => $expenseWater,
                'date' => now()->subDays(8),
                'description' => 'Pengisian dan penggantian air',
                'amount' => 175000,
            ],
            [
                'category' => $expenseTransport,
                'date' => now()->subDays(6),
                'description' => 'Transportasi pengambilan pakan',
                'amount' => 120000,
            ],
            [
                'category' => $expenseMaintenance,
                'date' => now()->subDays(5),
                'description' => 'Perawatan pompa kolam',
                'amount' => 275000,
            ],
            [
                'category' => $expenseLabor,
                'date' => now()->subDays(3),
                'description' => 'Upah tenaga kerja',
                'amount' => 500000,
            ],
            [
                'category' => $expenseOperational,
                'date' => now()->subDays(2),
                'description' => 'Kebutuhan operasional harian',
                'amount' => 150000,
            ],
        ];

        foreach ($expenses as $expenseData) {
            $expense = Expense::create([
                'category_id' => $expenseData['category']->id,
                'expense_date' => $expenseData['date']->toDateString(),
                'description' => $expenseData['description'],
                'amount' => $expenseData['amount'],
                'notes' => 'Data demo operasional.',
                'created_by' => $staff->id,
            ]);

            CashTransaction::create([
                'transaction_date' => $expense->expense_date,
                'type' => 'out',
                'category' => 'expense',
                'reference_type' => 'expense',
                'reference_id' => $expense->id,
                'description' => $expense->description,
                'amount' => $expense->amount,
                'created_by' => $staff->id,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | OTHER INCOME
        |--------------------------------------------------------------------------
        */

        $otherIncome = OtherIncome::create([
            'income_date' => now()->subDays(4)->toDateString(),
            'category' => 'Penjualan Barang Bekas',
            'description' => 'Penjualan karung pakan bekas',
            'amount' => 75000,
            'notes' => 'Pemasukan tambahan.',
            'created_by' => $staff->id,
        ]);

        CashTransaction::create([
            'transaction_date' => $otherIncome->income_date,
            'type' => 'in',
            'category' => 'other_income',
            'reference_type' => 'other_income',
            'reference_id' => $otherIncome->id,
            'description' => $otherIncome->description,
            'amount' => $otherIncome->amount,
            'created_by' => $staff->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | PURCHASE CASH TRANSACTIONS
        |--------------------------------------------------------------------------
        */

        foreach ([$purchase1, $purchase2, $purchase3] as $purchase) {
            CashTransaction::create([
                'transaction_date' => $purchase->purchase_date,
                'type' => 'out',
                'category' => 'purchase',
                'reference_type' => 'purchase',
                'reference_id' => $purchase->id,
                'description' => 'Pembelian ' . $purchase->invoice_number,
                'amount' => $purchase->total,
                'created_by' => $purchase->created_by,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | FINAL STOCK CALCULATION
        |--------------------------------------------------------------------------
        */

        $products = Product::all();

        foreach ($products as $product) {
            $stockIn = StockMovement::where('product_id', $product->id)
                ->where('type', 'in')
                ->sum('quantity');

            $stockOut = StockMovement::where('product_id', $product->id)
                ->where('type', 'out')
                ->sum('quantity');

            $adjustment = StockMovement::where('product_id', $product->id)
                ->where('type', 'adjustment')
                ->sum('quantity');

            $product->update([
                'current_stock' => $stockIn - $stockOut + $adjustment,
            ]);
        }
    }
}
