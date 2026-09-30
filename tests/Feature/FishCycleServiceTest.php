<?php

namespace Tests\Feature;

use App\Models\Feeding;
use App\Models\FishCycle;
use App\Models\Mortality;
use App\Models\Sampling;
use App\Models\Pond;
use App\Models\Product;
use App\Services\FishCycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FishCycleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FishCycleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(FishCycleService::class);
    }

    private function createCycle(int $initialFish = 1000): FishCycle
    {
        $pond = Pond::factory()->create();

        return FishCycle::create([
            'pond_id' => $pond->id,
            'code' => 'CYCLE-TEST-' . fake()->unique()->numerify('###'),
            'start_date' => '2026-09-01',
            'target_harvest_date' => '2026-12-01',
            'initial_fish_count' => $initialFish,
            'seed_size' => 5,
            'seed_unit_price' => 150,
            'status' => 'active',
        ]);
    }

    private function createFeedProduct(): Product
    {
        return Product::factory()->create();
    }

    public function test_living_fish_equals_initial_population_without_mortality(): void
    {
        $cycle = $this->createCycle(1000);

        $this->assertSame(
            1000,
            $this->service->livingFish($cycle)
        );
    }

    public function test_living_fish_is_reduced_by_mortality(): void
    {
        $cycle = $this->createCycle(1000);

        Mortality::create([
            'fish_cycle_id' => $cycle->id,
            'mortality_date' => '2026-09-10',
            'quantity' => 100,
            'cause' => 'Penyakit',
        ]);

        Mortality::create([
            'fish_cycle_id' => $cycle->id,
            'mortality_date' => '2026-09-15',
            'quantity' => 50,
            'cause' => 'Kematian alami',
        ]);

        $this->assertSame(
            850,
            $this->service->livingFish($cycle)
        );
    }

    public function test_total_mortality_is_calculated_correctly(): void
    {
        $cycle = $this->createCycle(1000);

        Mortality::create([
            'fish_cycle_id' => $cycle->id,
            'mortality_date' => '2026-09-10',
            'quantity' => 100,
            'cause' => 'Penyakit',
        ]);

        Mortality::create([
            'fish_cycle_id' => $cycle->id,
            'mortality_date' => '2026-09-15',
            'quantity' => 75,
            'cause' => 'Lainnya',
        ]);

        $this->assertSame(
            175,
            $this->service->totalMortality($cycle)
        );
    }

    public function test_survival_rate_is_calculated_correctly(): void
    {
        $cycle = $this->createCycle(1000);

        Mortality::create([
            'fish_cycle_id' => $cycle->id,
            'mortality_date' => '2026-09-10',
            'quantity' => 100,
            'cause' => 'Penyakit',
        ]);

        $this->assertSame(
            90.0,
            $this->service->survivalRate($cycle)
        );
    }

    public function test_total_feed_is_calculated_correctly(): void
    {
        $cycle = $this->createCycle();

        $product = $this->createFeedProduct();

        Feeding::create([
            'fish_cycle_id' => $cycle->id,
            'product_id' => $product->id,
            'feeding_date' => '2026-09-10',
            'quantity' => 5,
            'unit_price' => 10000,
            'total_cost' => 50000,
        ]);

        Feeding::create([
            'fish_cycle_id' => $cycle->id,
            'product_id' => $product->id,
            'feeding_date' => '2026-09-11',
            'quantity' => 7.5,
            'unit_price' => 10000,
            'total_cost' => 75000,
        ]);

        $this->assertSame(
            12.5,
            $this->service->totalFeed($cycle)
        );
    }

    public function test_total_feed_cost_is_calculated_correctly(): void
    {
        $cycle = $this->createCycle();

        $product = $this->createFeedProduct();

        Feeding::create([
            'fish_cycle_id' => $cycle->id,
            'product_id' => $product->id,
            'feeding_date' => '2026-09-10',
            'quantity' => 5,
            'unit_price' => 10000,
            'total_cost' => 50000,
        ]);

        Feeding::create([
            'fish_cycle_id' => $cycle->id,
            'product_id' => $product->id,
            'feeding_date' => '2026-09-11',
            'quantity' => 7.5,
            'unit_price' => 10000,
            'total_cost' => 75000,
        ]);
        $this->assertSame(
            125000.0,
            $this->service->totalFeedCost($cycle)
        );
    }

    public function test_latest_sampling_is_returned(): void
    {
        $cycle = $this->createCycle();

        Sampling::create([
            'fish_cycle_id' => $cycle->id,
            'sampling_date' => '2026-09-10',
            'sample_count' => 20,
            'average_weight' => 20,
            'average_length' => 10,
            'estimated_biomass' => 20,
            'estimated_population' => 1000,
        ]);

        $latest = Sampling::create([
            'fish_cycle_id' => $cycle->id,
            'sampling_date' => '2026-09-20',
            'sample_count' => 20,
            'average_weight' => 35,
            'average_length' => 13,
            'estimated_biomass' => 35,
            'estimated_population' => 1000,
        ]);

        $this->assertTrue(
            $this->service->latestSampling($cycle)?->is($latest)
        );
    }

    public function test_average_weight_uses_latest_sampling(): void
    {
        $cycle = $this->createCycle();

        Sampling::create([
            'fish_cycle_id' => $cycle->id,
            'sampling_date' => '2026-09-10',
            'sample_count' => 20,
            'average_weight' => 20,
            'average_length' => 10,
            'estimated_biomass' => 20,
            'estimated_population' => 1000,
        ]);

        Sampling::create([
            'fish_cycle_id' => $cycle->id,
            'sampling_date' => '2026-09-20',
            'sample_count' => 20,
            'average_weight' => 40,
            'average_length' => 13,
            'estimated_biomass' => 40,
            'estimated_population' => 1000,
        ]);

        $this->assertSame(
            40.0,
            $this->service->averageWeight($cycle)
        );
    }

    public function test_estimated_biomass_is_calculated_correctly(): void
    {
        $cycle = $this->createCycle(1000);

        Mortality::create([
            'fish_cycle_id' => $cycle->id,
            'mortality_date' => '2026-09-10',
            'quantity' => 100,
            'cause' => 'Penyakit',
        ]);

        Sampling::create([
            'fish_cycle_id' => $cycle->id,
            'sampling_date' => '2026-09-20',
            'sample_count' => 20,
            'average_weight' => 50,
            'average_length' => 15,
            'estimated_biomass' => 45,
            'estimated_population' => 900,
        ]);

        // 900 ikan x 50 gram = 45 kg
        $this->assertSame(
            45.0,
            $this->service->estimatedBiomass($cycle)
        );
    }

    public function test_fcr_is_calculated_correctly(): void
    {
        $cycle = $this->createCycle(1000);

        $product = $this->createFeedProduct();

        Feeding::create([
            'fish_cycle_id' => $cycle->id,
            'product_id' => $product->id,
            'feeding_date' => '2026-09-10',
            'quantity' => 9,
            'unit_price' => 10000,
            'total_cost' => 90000,
        ]);
        Sampling::create([
            'fish_cycle_id' => $cycle->id,
            'sampling_date' => '2026-09-20',
            'sample_count' => 20,
            'average_weight' => 50,
            'average_length' => 15,
            'estimated_biomass' => 50,
            'estimated_population' => 1000,
        ]);

        // 9 kg pakan / 50 kg biomassa = 0.18
        $this->assertSame(
            0.18,
            $this->service->fcr($cycle)
        );
    }

    public function test_summary_returns_complete_cycle_metrics(): void
    {
        $cycle = $this->createCycle(1000);

        Mortality::create([
            'fish_cycle_id' => $cycle->id,
            'mortality_date' => '2026-09-10',
            'quantity' => 100,
            'cause' => 'Penyakit',
        ]);

        $product = $this->createFeedProduct();

        Feeding::create([
            'fish_cycle_id' => $cycle->id,
            'product_id' => $product->id,
            'feeding_date' => '2026-09-10',
            'quantity' => 9,
            'unit_price' => 10000,
            'total_cost' => 90000,
        ]);

        Sampling::create([
            'fish_cycle_id' => $cycle->id,
            'sampling_date' => '2026-09-20',
            'sample_count' => 20,
            'average_weight' => 50,
            'average_length' => 15,
            'estimated_biomass' => 45,
            'estimated_population' => 900,
        ]);

        $summary = $this->service->summary($cycle);

        $this->assertSame(1000, $summary['initial_fish']);
        $this->assertSame(100, $summary['total_mortality']);
        $this->assertSame(900, $summary['living_fish']);
        $this->assertSame(90.0, $summary['survival_rate']);
        $this->assertSame(9.0, $summary['total_feed']);
        $this->assertSame(90000.0, $summary['total_feed_cost']);
        $this->assertSame(50.0, $summary['average_weight']);
        $this->assertSame(45.0, $summary['estimated_biomass']);
        $this->assertSame(0.2, $summary['fcr']);
    }

    public function test_add_mortality_rejects_quantity_greater_than_living_fish(): void
    {
        $cycle = $this->createCycle(100);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Jumlah mortalitas melebihi ikan yang masih hidup.'
        );

        $this->service->addMortality(
            $cycle,
            101,
            'Penyakit'
        );
    }

    public function test_add_sampling_rejects_invalid_sample_count(): void
    {
        $cycle = $this->createCycle();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Jumlah sample harus lebih besar dari 0.'
        );

        $this->service->addSampling(
            $cycle,
            0,
            50,
            15
        );
    }
}
