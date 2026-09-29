<?php

namespace App\Services;

use App\Models\FishCycle;
use App\Models\Feeding;
use App\Models\Mortality;
use App\Models\Sampling;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FishCycleService
{
    /**
     * Jumlah ikan yang masih hidup.
     */
    public function livingFish(FishCycle $cycle): int
    {
        $initial = (int) $cycle->initial_fish_count;

        $mortality = Mortality::where('fish_cycle_id', $cycle->id)
            ->sum('quantity');

        return max(0, $initial - (int) $mortality);
    }

    /**
     * Total ikan yang mati.
     */
    public function totalMortality(FishCycle $cycle): int
    {
        return (int) Mortality::where('fish_cycle_id', $cycle->id)
            ->sum('quantity');
    }

    /**
     * Survival Rate (%).
     */
    public function survivalRate(FishCycle $cycle): float
    {
        $initial = (int) $cycle->initial_fish_count;

        if ($initial <= 0) {
            return 0;
        }

        return round(
            ($this->livingFish($cycle) / $initial) * 100,
            2
        );
    }

    /**
     * Total pakan yang telah digunakan.
     */
    public function totalFeed(FishCycle $cycle): float
    {
        return (float) Feeding::where('fish_cycle_id', $cycle->id)
            ->sum('quantity');
    }

    /**
     * Total biaya pakan.
     */
    public function totalFeedCost(FishCycle $cycle): float
    {
        return (float) Feeding::where('fish_cycle_id', $cycle->id)
            ->sum('total_cost');
    }

    /**
     * Sampling terakhir.
     */
    public function latestSampling(FishCycle $cycle): ?Sampling
    {
        return Sampling::where('fish_cycle_id', $cycle->id)
            ->latest('sampling_date')
            ->first();
    }

    /**
     * Berat rata-rata ikan berdasarkan sampling terakhir.
     * Satuan gram.
     */
    public function averageWeight(FishCycle $cycle): float
    {
        $sampling = $this->latestSampling($cycle);

        return $sampling
            ? (float) $sampling->average_weight
            : 0;
    }

    /**
     * Estimasi biomassa berdasarkan populasi hidup
     * dan berat rata-rata.
     *
     * gram → kilogram
     */
    public function estimatedBiomass(FishCycle $cycle): float
    {
        $livingFish = $this->livingFish($cycle);
        $averageWeight = $this->averageWeight($cycle);

        if ($livingFish <= 0 || $averageWeight <= 0) {
            return 0;
        }

        return round(
            ($livingFish * $averageWeight) / 1000,
            2
        );
    }

    /**
     * FCR sederhana berdasarkan total pakan
     * dibandingkan dengan biomassa ikan saat ini.
     *
     * FCR = total pakan / biomassa
     */
    public function fcr(FishCycle $cycle): float
    {
        $feed = $this->totalFeed($cycle);
        $biomass = $this->estimatedBiomass($cycle);

        if ($feed <= 0 || $biomass <= 0) {
            return 0;
        }

        return round($feed / $biomass, 2);
    }

    /**
     * Membuat ringkasan lengkap siklus.
     */
    public function summary(FishCycle $cycle): array
    {
        return [
            'initial_fish' => (int) $cycle->initial_fish_count,

            'total_mortality' => $this->totalMortality($cycle),

            'living_fish' => $this->livingFish($cycle),

            'survival_rate' => $this->survivalRate($cycle),

            'total_feed' => $this->totalFeed($cycle),

            'total_feed_cost' => $this->totalFeedCost($cycle),

            'average_weight' => $this->averageWeight($cycle),

            'estimated_biomass' => $this->estimatedBiomass($cycle),

            'fcr' => $this->fcr($cycle),
        ];
    }

    /**
     * Tambahkan mortalitas.
     */
    public function addMortality(
        FishCycle $cycle,
        int $quantity,
        string $cause,
        ?string $notes = null,
        ?int $userId = null
    ): Mortality {
        if ($quantity <= 0) {
            throw new InvalidArgumentException(
                'Jumlah mortalitas harus lebih besar dari 0.'
            );
        }

        $livingFish = $this->livingFish($cycle);

        if ($quantity > $livingFish) {
            throw new InvalidArgumentException(
                'Jumlah mortalitas melebihi ikan yang masih hidup.'
            );
        }

        return DB::transaction(function () use (
            $cycle,
            $quantity,
            $cause,
            $notes,
            $userId
        ) {
            return Mortality::create([
                'fish_cycle_id' => $cycle->id,
                'mortality_date' => now()->toDateString(),
                'quantity' => $quantity,
                'cause' => $cause,
                'notes' => $notes,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Tambahkan sampling.
     */
    public function addSampling(
        FishCycle $cycle,
        int $sampleCount,
        float $averageWeight,
        float $averageLength,
        ?string $notes = null,
        ?int $userId = null
    ): Sampling {
        if ($sampleCount <= 0) {
            throw new InvalidArgumentException(
                'Jumlah sample harus lebih besar dari 0.'
            );
        }

        if ($averageWeight <= 0) {
            throw new InvalidArgumentException(
                'Berat rata-rata harus lebih besar dari 0.'
            );
        }

        if ($averageLength <= 0) {
            throw new InvalidArgumentException(
                'Panjang rata-rata harus lebih besar dari 0.'
            );
        }

        $livingFish = $this->livingFish($cycle);

        $estimatedBiomass =
            ($livingFish * $averageWeight) / 1000;

        return DB::transaction(function () use (
            $cycle,
            $sampleCount,
            $averageWeight,
            $averageLength,
            $estimatedBiomass,
            $livingFish,
            $notes,
            $userId
        ) {
            return Sampling::create([
                'fish_cycle_id' => $cycle->id,
                'sampling_date' => now()->toDateString(),
                'sample_count' => $sampleCount,
                'average_weight' => $averageWeight,
                'average_length' => $averageLength,
                'estimated_biomass' => round($estimatedBiomass, 2),
                'estimated_population' => $livingFish,
                'notes' => $notes,
                'created_by' => $userId,
            ]);
        });
    }
}
