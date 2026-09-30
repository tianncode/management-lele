<?php

namespace Tests\Feature;

use App\Models\FishCycle;
use App\Models\Mortality;
use App\Models\Sampling;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SamplingApiTest extends TestCase
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

    public function test_can_list_samplings(): void
    {
        Sampling::factory()->count(3)->create();

        $response = $this->getJson('/api/samplings');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'fish_cycle',
                        'sampling_date',
                        'sample_count',
                        'average_weight',
                        'average_length',
                        'estimated_population',
                        'estimated_biomass',
                        'notes',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_can_create_sampling(): void
    {
        $cycle = FishCycle::factory()->create([
            'initial_fish_count' => 1000,
        ]);

        $response = $this->postJson('/api/samplings', [
            'fish_cycle_id' => $cycle->id,
            'sampling_date' => '2026-09-30',
            'sample_count' => 50,
            'average_weight' => 25.50,
            'average_length' => 15.50,
            'notes' => 'Sampling minggu pertama.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.fish_cycle.id', $cycle->id)
            ->assertJsonPath('data.sample_count', 50)
            ->assertJsonPath('data.average_weight', 25.5)
            ->assertJsonPath('data.estimated_population', 1000)
            ->assertJsonPath('data.estimated_biomass', 25.5);

        $this->assertDatabaseHas('samplings', [
            'fish_cycle_id' => $cycle->id,
            'sample_count' => 50,
        ]);
    }

    public function test_sampling_calculates_population_after_mortality(): void
    {
        $cycle = FishCycle::factory()->create([
            'initial_fish_count' => 1000,
        ]);

        Mortality::create([
            'fish_cycle_id' => $cycle->id,
            'mortality_date' => '2026-09-29',
            'quantity' => 100,
            'cause' => 'Penyakit',
        ]);

        $response = $this->postJson('/api/samplings', [
            'fish_cycle_id' => $cycle->id,
            'sampling_date' => '2026-09-30',
            'sample_count' => 50,
            'average_weight' => 20,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.estimated_population', 900)
            ->assertJsonPath('data.estimated_biomass', 18);
    }

    public function test_can_show_sampling(): void
    {
        $sampling = Sampling::factory()->create();

        $response = $this->getJson(
            "/api/samplings/{$sampling->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $sampling->id)
            ->assertJsonPath(
                'data.fish_cycle.id',
                $sampling->fish_cycle_id
            );
    }

    public function test_sampling_requires_valid_data(): void
    {
        $response = $this->postJson('/api/samplings', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'fish_cycle_id',
                'sampling_date',
                'sample_count',
                'average_weight',
            ]);
    }

    public function test_sample_count_must_be_at_least_one(): void
    {
        $cycle = FishCycle::factory()->create();

        $response = $this->postJson('/api/samplings', [
            'fish_cycle_id' => $cycle->id,
            'sampling_date' => '2026-09-30',
            'sample_count' => 0,
            'average_weight' => 20,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'sample_count',
            ]);
    }

    public function test_average_weight_must_be_greater_than_zero(): void
    {
        $cycle = FishCycle::factory()->create();

        $response = $this->postJson('/api/samplings', [
            'fish_cycle_id' => $cycle->id,
            'sampling_date' => '2026-09-30',
            'sample_count' => 50,
            'average_weight' => 0,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'average_weight',
            ]);
    }

    public function test_average_length_must_be_greater_than_zero_when_provided(): void
    {
        $cycle = FishCycle::factory()->create();

        $response = $this->postJson('/api/samplings', [
            'fish_cycle_id' => $cycle->id,
            'sampling_date' => '2026-09-30',
            'sample_count' => 50,
            'average_weight' => 20,
            'average_length' => 0,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'average_length',
            ]);
    }

    public function test_update_sampling_is_disabled(): void
    {
        $sampling = Sampling::factory()->create();

        $response = $this->putJson(
            "/api/samplings/{$sampling->id}",
            [
                'fish_cycle_id' => $sampling->fish_cycle_id,
                'sampling_date' => '2026-09-30',
                'sample_count' => 50,
                'average_weight' => 30,
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' =>
                'Update sampling akan diaktifkan setelah mekanisme koreksi data sampling selesai.',
            ]);
    }

    public function test_delete_sampling_is_disabled(): void
    {
        $sampling = Sampling::factory()->create();

        $response = $this->deleteJson(
            "/api/samplings/{$sampling->id}"
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' =>
                'Penghapusan sampling akan diaktifkan setelah mekanisme koreksi data sampling selesai.',
            ]);

        $this->assertDatabaseHas('samplings', [
            'id' => $sampling->id,
        ]);
    }

    public function test_show_returns_404_for_non_existing_sampling(): void
    {
        $response = $this->getJson('/api/samplings/999999');

        $response->assertNotFound();
    }
}
