<?php

namespace App\Services;

use App\Models\FishCycle;
use App\Models\Sampling;
use Illuminate\Support\Facades\DB;

class SamplingService
{
    public function create(array $data): Sampling
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
                ->sum('quantity');

            $estimatedPopulation = max(
                0,
                $initialPopulation
                    - $totalMortality
                    - $totalHarvested
            );

            $averageWeight = (float) $data['average_weight'];

            $estimatedBiomass =
                ($estimatedPopulation * $averageWeight) / 1000;

            return Sampling::create([
                'fish_cycle_id' => $cycle->id,
                'sampling_date' => $data['sampling_date'],
                'sample_count' => $data['sample_count'],
                'average_weight' => $averageWeight,
                'average_length' => $data['average_length'] ?? null,
                'estimated_biomass' => $estimatedBiomass,
                'estimated_population' => $estimatedPopulation,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }
}
