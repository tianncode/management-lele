<?php

namespace App\Services;

use App\Models\FishCycle;
use App\Models\Mortality;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MortalityService
{
    public function create(array $data): Mortality
    {
        return DB::transaction(function () use ($data) {
            $cycle = FishCycle::query()
                ->lockForUpdate()
                ->findOrFail($data['fish_cycle_id']);

            $initialFish = (int) $cycle->initial_fish_count;

            $existingMortality = (int) $cycle
                ->mortalities()
                ->sum('quantity');

            $availableFish = $initialFish - $existingMortality;

            $quantity = (int) $data['quantity'];

            if ($quantity > $availableFish) {
                throw new RuntimeException(
                    "Jumlah ikan mati melebihi ikan yang tersedia. " .
                    "Ikan tersedia: {$availableFish} ekor."
                );
            }

            return Mortality::create([
                'fish_cycle_id' => $cycle->id,
                'mortality_date' => $data['mortality_date'],
                'quantity' => $quantity,
                'cause' => $data['cause'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }
}
