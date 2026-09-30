<?php

namespace Tests\Feature;

use App\Models\FishCycle;
use App\Models\Pond;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FishCycleApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->actingAs($this->user, 'sanctum');
    }

    public function test_can_list_fish_cycles(): void
    {
        FishCycle::factory()
            ->count(3)
            ->create();

        $response = $this->getJson('/api/fish-cycles');

        $response
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'pond',
                        'start_date',
                        'target_harvest_date',
                        'initial_fish_count',
                        'initial_average_weight',
                        'target_average_weight',
                        'status',
                        'notes',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_can_create_fish_cycle(): void
    {
        $pond = Pond::factory()->create();

        $response = $this->postJson('/api/fish-cycles', [
            'pond_id' => $pond->id,
            'code' => 'CYCLE-001',
            'start_date' => '2026-09-30',
            'target_harvest_date' => '2027-01-30',
            'initial_fish_count' => 5000,
            'initial_average_weight' => 5,
            'target_average_weight' => 150,
            'status' => 'active',
            'notes' => 'Siklus budidaya baru.',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                ],
            ]);

        $this->assertDatabaseHas('fish_cycles', [
            'pond_id' => $pond->id,
            'code' => 'CYCLE-001',
            'initial_fish_count' => 5000,
            'status' => 'active',
        ]);
    }

    public function test_can_show_fish_cycle(): void
    {
        $cycle = FishCycle::factory()->create([
            'code' => 'CYCLE-001',
        ]);

        $response = $this->getJson(
            "/api/fish-cycles/{$cycle->id}"
        );

        $response
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'code',
                    'pond',
                    'start_date',
                    'target_harvest_date',
                    'initial_fish_count',
                    'initial_average_weight',
                    'target_average_weight',
                    'status',
                    'notes',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', $cycle->id)
            ->assertJsonPath('data.code', 'CYCLE-001');
    }

    public function test_can_update_fish_cycle(): void
    {
        $cycle = FishCycle::factory()->create([
            'code' => 'CYCLE-001',
        ]);

        $response = $this->putJson(
            "/api/fish-cycles/{$cycle->id}",
            [
                'pond_id' => $cycle->pond_id,
                'code' => 'CYCLE-001',
                'start_date' => $cycle->start_date->format('Y-m-d'),
                'target_harvest_date' => '2027-02-15',
                'initial_fish_count' => 6000,
                'initial_average_weight' => 5,
                'target_average_weight' => 150,
                'status' => 'active',
                'notes' => 'Siklus diperbarui.',
            ]
        );

        $response
            ->assertSuccessful()
            ->assertJsonPath('data.code', 'CYCLE-001');

        $this->assertDatabaseHas('fish_cycles', [
            'id' => $cycle->id,
            'initial_fish_count' => 6000,
            'status' => 'active',
        ]);
    }

    public function test_can_delete_fish_cycle(): void
    {
        $cycle = FishCycle::factory()->create();

        $response = $this->deleteJson(
            "/api/fish-cycles/{$cycle->id}"
        );

        $response->assertSuccessful();

        $this->assertDatabaseMissing('fish_cycles', [
            'id' => $cycle->id,
        ]);
    }

    public function test_pond_id_is_required(): void
    {
        $response = $this->postJson('/api/fish-cycles', [
            'code' => 'CYCLE-001',
            'start_date' => '2026-09-30',
            'initial_fish_count' => 5000,
            'status' => 'active',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'pond_id',
            ]);
    }

    public function test_pond_must_exist(): void
    {
        $response = $this->postJson('/api/fish-cycles', [
            'pond_id' => 999999,
            'code' => 'CYCLE-001',
            'start_date' => '2026-09-30',
            'initial_fish_count' => 5000,
            'status' => 'active',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'pond_id',
            ]);
    }

    public function test_name_is_required(): void
    {
        $pond = Pond::factory()->create();

        $response = $this->postJson('/api/fish-cycles', [
            'pond_id' => $pond->id,
            'code' => 'CYCLE-001',
            'start_date' => '2026-09-30',
            'initial_fish_count' => 5000,
            'status' => 'active',
        ]);

        $response->assertSuccessful();
    }

    public function test_initial_population_must_be_positive(): void
    {
        $pond = Pond::factory()->create();

        $response = $this->postJson('/api/fish-cycles', [
            'pond_id' => $pond->id,
            'code' => 'CYCLE-001',
            'start_date' => '2026-09-30',
            'initial_fish_count' => 0,
            'status' => 'active',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'initial_fish_count',
            ]);
    }

    public function test_show_returns_404_for_non_existing_cycle(): void
    {
        $response = $this->getJson('/api/fish-cycles/999999');

        $response->assertNotFound();
    }

    public function test_update_returns_404_for_non_existing_cycle(): void
    {
        $pond = Pond::factory()->create();

        $response = $this->putJson(
            '/api/fish-cycles/999999',
            [
                'pond_id' => $pond->id,
                'code' => 'CYCLE-001',
                'start_date' => '2026-09-30',
                'initial_fish_count' => 5000,
                'status' => 'active',
            ]
        );

        $response->assertNotFound();
    }

    public function test_delete_returns_404_for_non_existing_cycle(): void
    {
        $response = $this->deleteJson(
            '/api/fish-cycles/999999'
        );

        $response->assertNotFound();
    }
}
