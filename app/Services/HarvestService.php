<?php

namespace App\Services;

use App\Models\FishCycle;
use App\Models\Harvest;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HarvestService
{
    public function create(array $data): Harvest
    {
        return DB::transaction(function () use ($data) {
            $cycle = FishCycle::query()
                ->lockForUpdate()
                ->findOrFail($data['fish_cycle_id']);

            $initialPopulation = (int) $cycle->initial_fish_count;

            $totalMortality = (int) $cycle
                ->mortalities()
                ->sum('quantity');

            $totalHarvested = (int) $cycle
                ->harvests()
                ->sum('total_fish');

            $availableFish = max(
                0,
                $initialPopulation
                    - $totalMortality
                    - $totalHarvested
            );

            $totalFish = (int) $data['total_fish'];

            if ($totalFish > $availableFish) {
                throw new RuntimeException(
                    "Jumlah ikan yang dipanen melebihi populasi tersedia. " .
                        "Ikan tersedia: {$availableFish} ekor."
                );
            }

            $totalWeight = (float) $data['total_weight'];

            $sellingPrice = (float) $data['selling_price_per_kg'];

            /*
             * Berat rata-rata ikan hasil panen.
             *
             * total_weight dalam kilogram,
             * kemudian dikonversi ke gram.
             */
            $averageWeight =
                ($totalWeight * 1000) / $totalFish;

            /*
             * Estimasi pendapatan.
             */
            $estimatedRevenue =
                $totalWeight * $sellingPrice;

            $harvest = Harvest::create([
                'fish_cycle_id' => $cycle->id,
                'harvest_date' => $data['harvest_date'],
                'total_fish' => $totalFish,
                'total_weight' => $totalWeight,
                'average_weight' => $averageWeight,
                'selling_price_per_kg' => $sellingPrice,
                'estimated_revenue' => $estimatedRevenue,
                'notes' => $data['notes'] ?? null,
            ]);

            /*
             * Jika seluruh ikan sudah dipanen,
             * siklus otomatis selesai.
             */
            $remainingFish =
                $availableFish - $totalFish;

            if ($remainingFish === 0) {
                $cycle->update([
                    'status' => 'completed',
                ]);
            } elseif ($cycle->status !== 'harvest') {
                $cycle->update([
                    'status' => 'harvest',
                ]);
            }

            return $harvest->load('fishCycle');
        });
    }
}
