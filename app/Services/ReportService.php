<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\FishCycle;
use App\Models\Harvest;
use App\Models\Mortality;
use App\Models\Pond;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(
        protected FishCycleService $fishCycleService
    ) {}

    /**
     * Ringkasan utama dashboard.
     */
    public function dashboard(): array
    {
        return [
            'ponds' => $this->pondSummary(),
            'fish' => $this->fishSummary(),
            'feed' => $this->feedSummary(),
            'finance' => $this->financeSummary(),
            'sales' => $this->salesSummary(),
            'purchases' => $this->purchaseSummary(),
            'alerts' => $this->alerts(),
        ];
    }

    /**
     * Ringkasan kolam.
     */
    public function pondSummary(): array
    {
        $ponds = Pond::query();

        return [
            'total' => (clone $ponds)->count(),

            'active' => (clone $ponds)
                ->where('status', 'cultivation')
                ->count(),

            'available' => (clone $ponds)
                ->where('status', 'available')
                ->count(),

            'harvest' => (clone $ponds)
                ->where('status', 'harvest')
                ->count(),

            'maintenance' => (clone $ponds)
                ->where('status', 'maintenance')
                ->count(),

            'preparation' => (clone $ponds)
                ->where('status', 'preparation')
                ->count(),
        ];
    }

    /**
     * Ringkasan populasi ikan.
     */
    public function fishSummary(): array
    {
        $cycles = FishCycle::whereIn('status', [
            'active',
            'cultivation',
        ])->get();

        $initialFish = 0;
        $mortality = 0;
        $livingFish = 0;
        $biomass = 0;

        foreach ($cycles as $cycle) {
            $initialFish += (int) $cycle->initial_fish_count;
            $mortality += $this->fishCycleService
                ->totalMortality($cycle);

            $livingFish += $this->fishCycleService
                ->livingFish($cycle);

            $biomass += $this->fishCycleService
                ->estimatedBiomass($cycle);
        }

        $survivalRate = $initialFish > 0
            ? round(($livingFish / $initialFish) * 100, 2)
            : 0;

        return [
            'initial_fish' => $initialFish,
            'mortality' => $mortality,
            'living_fish' => $livingFish,
            'survival_rate' => $survivalRate,
            'biomass' => round($biomass, 2),
        ];
    }

    /**
     * Ringkasan pakan.
     */
    public function feedSummary(): array
    {
        $cycles = FishCycle::whereIn('status', [
            'active',
            'cultivation',
        ])->get();

        $totalFeed = 0;
        $totalFeedCost = 0;

        foreach ($cycles as $cycle) {
            $totalFeed += $this->fishCycleService
                ->totalFeed($cycle);

            $totalFeedCost += $this->fishCycleService
                ->totalFeedCost($cycle);
        }

        return [
            'total_feed' => round($totalFeed, 2),
            'total_cost' => round($totalFeedCost, 2),
        ];
    }

    /**
     * Ringkasan keuangan.
     */
    public function financeSummary(): array
    {
        $income = CashTransaction::where('type', 'in')
            ->sum('amount');

        $expense = CashTransaction::where('type', 'out')
            ->sum('amount');

        return [
            'income' => (float) $income,
            'expense' => (float) $expense,
            'balance' => (float) $income - (float) $expense,
        ];
    }

    /**
     * Ringkasan penjualan.
     */
    public function salesSummary(): array
    {
        $totalSales = Sale::sum('total');

        $paidSales = Sale::where('payment_status', 'paid')
            ->sum('total');

        $unpaidSales = Sale::where('payment_status', '!=', 'paid')
            ->sum('total');

        $harvestWeight = Harvest::sum('total_weight');

        return [
            'total_sales' => (float) $totalSales,
            'paid_sales' => (float) $paidSales,
            'unpaid_sales' => (float) $unpaidSales,
            'harvest_weight' => (float) $harvestWeight,
        ];
    }

    /**
     * Ringkasan pembelian.
     */
    public function purchaseSummary(): array
    {
        $total = Purchase::sum('total');

        $paid = Purchase::where('payment_status', 'paid')
            ->sum('total');

        $unpaid = Purchase::where('payment_status', '!=', 'paid')
            ->sum('total');

        return [
            'total' => (float) $total,
            'paid' => (float) $paid,
            'unpaid' => (float) $unpaid,
        ];
    }

    /**
     * Peringatan dashboard.
     */
    public function alerts(): array
    {
        $lowStock = Product::query()
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->get([
                'id',
                'code',
                'name',
                'unit',
                'current_stock',
                'minimum_stock',
            ]);

        $highMortality = Mortality::query()
            ->select(
                'fish_cycle_id',
                DB::raw('SUM(quantity) as total_mortality')
            )
            ->groupBy('fish_cycle_id')
            ->having('total_mortality', '>=', 100)
            ->get();

        return [
            'low_stock' => $lowStock,
            'high_mortality' => $highMortality,
        ];
    }
}
