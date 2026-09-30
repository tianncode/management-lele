<?php

namespace Tests\Feature;

use App\Models\FishCycle;
use App\Models\Harvest;
use App\Models\Mortality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HarvestApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'staff',
        ]);

        $this->actingAs($this->user, 'sanctum');
    }

    public function test_can_list_harvests(): void
    {
        Harvest::factory()->count(3)->create();

        $response = $this->getJson('/api/harvests');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'fish_cycle',
                        'harvest_date',
                        'total_fish',
                        'total_weight',
                        'average_weight',
                        'selling_price_per_kg',
                        'estimated_revenue',
                        'notes',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_can_create_harvest(): void
    {
        $cycle = FishCycle::factory()->create([
            'initial_fish_count' => 1000,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/harvests', [
            'fish_cycle_id' => $cycle->id,
            'harvest_date' => '2026-09-30',
            'total_fish' => 200,
            'total_weight' => 50,
            'selling_price_per_kg' => 30000,
            'notes' => 'Panen pertama.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.fish_cycle.id', $cycle->id)
            ->assertJsonPath('data.total_fish', 200)
            ->assertJsonPath('data.total_weight', 50)
            ->assertJsonPath('data.average_weight', 250)
            ->assertJsonPath('data.selling_price_per_kg', 30000)
            ->assertJsonPath('data.estimated_revenue', 1500000);

        $this->assertDatabaseHas('harvests', [
            'fish_cycle_id' => $cycle->id,
            'total_fish' => 200,
        ]);
    }

    public function test_harvest_calculates_available_population_after_mortality(): void
    {
        $cycle = FishCycle::factory()->create([
            'initial_fish_count' => 1000,
            'status' => 'active',
        ]);

        Mortality::create([
            'fish_cycle_id' => $cycle->id,
            'mortality_date' => '2026-09-29',
            'quantity' => 100,
            'cause' => 'Penyakit',
        ]);

        $response = $this->postJson('/api/harvests', [
            'fish_cycle_id' => $cycle->id,
            'harvest_date' => '2026-09-30',
            'total_fish' => 900,
            'total_weight' => 225,
            'selling_price_per_kg' => 30000,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.total_fish', 900)
            ->assertJsonPath('data.average_weight', 250)
            ->assertJsonPath('data.estimated_revenue', 6750000);
    }

    public function test_harvest_cannot_exceed_available_fish(): void
    {
        $cycle = FishCycle::factory()->create([
            'initial_fish_count' => 100,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/harvests', [
            'fish_cycle_id' => $cycle->id,
            'harvest_date' => '2026-09-30',
            'total_fish' => 101,
            'total_weight' => 25,
            'selling_price_per_kg' => 30000,
        ]);

        $response
            ->assertStatus(500)
            ->assertJsonFragment([
                'message' =>
                'Jumlah ikan yang dipanen melebihi populasi tersedia. Ikan tersedia: 100 ekor.',
            ]);
    }

    public function test_harvest_cannot_exceed_remaining_population_after_previous_harvest(): void
    {
        $cycle = FishCycle::factory()->create([
            'initial_fish_count' => 100,
            'status' => 'active',
        ]);

        Harvest::create([
            'fish_cycle_id' => $cycle->id,
            'harvest_date' => '2026-09-29',
            'total_fish' => 70,
            'total_weight' => 17.5,
            'average_weight' => 250,
            'selling_price_per_kg' => 30000,
            'estimated_revenue' => 525000,
        ]);

        $response = $this->postJson('/api/harvests', [
            'fish_cycle_id' => $cycle->id,
            'harvest_date' => '2026-09-30',
            'total_fish' => 31,
            'total_weight' => 7.75,
            'selling_price_per_kg' => 30000,
        ]);

        $response
            ->assertStatus(500)
            ->assertJsonFragment([
                'message' =>
                'Jumlah ikan yang dipanen melebihi populasi tersedia. Ikan tersedia: 30 ekor.',
            ]);
    }

    public function test_harvest_changes_cycle_status_to_harvest_when_fish_remain(): void
    {
        $cycle = FishCycle::factory()->create([
            'initial_fish_count' => 100,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/harvests', [
            'fish_cycle_id' => $cycle->id,
            'harvest_date' => '2026-09-30',
            'total_fish' => 60,
            'total_weight' => 15,
            'selling_price_per_kg' => 30000,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('fish_cycles', [
            'id' => $cycle->id,
            'status' => 'harvest',
        ]);
    }

    public function test_harvest_changes_cycle_status_to_completed_when_all_fish_are_harvested(): void
    {
        $cycle = FishCycle::factory()->create([
            'initial_fish_count' => 100,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/harvests', [
            'fish_cycle_id' => $cycle->id,
            'harvest_date' => '2026-09-30',
            'total_fish' => 100,
            'total_weight' => 25,
            'selling_price_per_kg' => 30000,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('fish_cycles', [
            'id' => $cycle->id,
            'status' => 'completed',
        ]);
    }

    public function test_harvest_requires_valid_data(): void
    {
        $response = $this->postJson('/api/harvests', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'fish_cycle_id',
                'harvest_date',
                'total_fish',
                'total_weight',
                'selling_price_per_kg',
            ]);
    }

    public function test_total_fish_must_be_at_least_one(): void
    {
        $cycle = FishCycle::factory()->create();

        $response = $this->postJson('/api/harvests', [
            'fish_cycle_id' => $cycle->id,
            'harvest_date' => '2026-09-30',
            'total_fish' => 0,
            'total_weight' => 10,
            'selling_price_per_kg' => 30000,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'total_fish',
            ]);
    }

    public function test_total_weight_must_be_greater_than_zero(): void
    {
        $cycle = FishCycle::factory()->create();

        $response = $this->postJson('/api/harvests', [
            'fish_cycle_id' => $cycle->id,
            'harvest_date' => '2026-09-30',
            'total_fish' => 100,
            'total_weight' => 0,
            'selling_price_per_kg' => 30000,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'total_weight',
            ]);
    }

    public function test_selling_price_can_be_zero(): void
    {
        $cycle = FishCycle::factory()->create([
            'initial_fish_count' => 100,
        ]);

        $response = $this->postJson('/api/harvests', [
            'fish_cycle_id' => $cycle->id,
            'harvest_date' => '2026-09-30',
            'total_fish' => 50,
            'total_weight' => 10,
            'selling_price_per_kg' => 0,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.selling_price_per_kg', 0)
            ->assertJsonPath('data.estimated_revenue', 0);
    }

    public function test_can_show_harvest(): void
    {
        $harvest = Harvest::factory()->create();

        $response = $this->getJson(
            "/api/harvests/{$harvest->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $harvest->id)
            ->assertJsonPath(
                'data.fish_cycle.id',
                $harvest->fish_cycle_id
            );
    }

    public function test_update_harvest_is_disabled(): void
    {
        $harvest = Harvest::factory()->create();

        $response = $this->putJson(
            "/api/harvests/{$harvest->id}",
            [
                'fish_cycle_id' => $harvest->fish_cycle_id,
                'harvest_date' => '2026-09-30',
                'total_fish' => 50,
                'total_weight' => 10,
                'selling_price_per_kg' => 30000,
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' =>
                'Update harvest akan diaktifkan setelah mekanisme koreksi stok dan transaksi penjualan selesai.',
            ]);
    }

    public function test_delete_harvest_is_disabled(): void
    {
        $harvest = Harvest::factory()->create();

        $response = $this->deleteJson(
            "/api/harvests/{$harvest->id}"
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' =>
                'Penghapusan harvest akan diaktifkan setelah mekanisme koreksi stok dan transaksi penjualan selesai.',
            ]);

        $this->assertDatabaseHas('harvests', [
            'id' => $harvest->id,
        ]);
    }

    public function test_show_returns_404_for_non_existing_harvest(): void
    {
        $response = $this->getJson('/api/harvests/999999');

        $response->assertNotFound();
    }
}
