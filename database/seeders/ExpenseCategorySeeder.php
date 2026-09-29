<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Pakan',
                'description' => 'Pembelian atau kebutuhan pakan di luar pembelian stok utama.',
                'is_active' => true,
            ],
            [
                'name' => 'Obat dan Vitamin',
                'description' => 'Obat, vitamin, probiotik, dan kebutuhan kesehatan ikan.',
                'is_active' => true,
            ],
            [
                'name' => 'Listrik',
                'description' => 'Biaya listrik untuk pompa, aerator, dan fasilitas peternakan.',
                'is_active' => true,
            ],
            [
                'name' => 'Air',
                'description' => 'Biaya air dan kebutuhan pengelolaan air kolam.',
                'is_active' => true,
            ],
            [
                'name' => 'Perawatan Kolam',
                'description' => 'Perawatan dan perbaikan kolam.',
                'is_active' => true,
            ],
            [
                'name' => 'Transportasi',
                'description' => 'Biaya transportasi operasional peternakan.',
                'is_active' => true,
            ],
            [
                'name' => 'Tenaga Kerja',
                'description' => 'Upah atau biaya tenaga kerja operasional.',
                'is_active' => true,
            ],
            [
                'name' => 'Peralatan',
                'description' => 'Pembelian atau perbaikan peralatan peternakan.',
                'is_active' => true,
            ],
            [
                'name' => 'Operasional Lainnya',
                'description' => 'Pengeluaran operasional lainnya.',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
