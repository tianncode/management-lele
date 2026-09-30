<?php

namespace Tests\Feature;

use App\Models\FishCycle;
use App\Models\Mortality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MortalityApiTest extends TestCase
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

    public function test_can_list_mortalities(): void
    {
        Mortality::factory()->count(3)->create();

        $response = $this->getJson('/api/mortalities');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'fish_cycle',
                        'mortality_date',
                        'quantity',
                        'cause',
                        'notes',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_can_create_mortality(): void
    {
        $cycle = FishCycle::factory()->create([
            'initial_fish_count' => 1000,
        ]);

        $payload = [
            'fish_cycle_id' => $cycle->id,
            'mortality_date' => '2026-09-30',
            'quantity' => 25,
            'cause' => 'Kualitas air',
            'notes' => 'Ditemukan setelah pemeriksaan pagi.',
        ];

        $response = $this->postJson('/api/mortalities', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('data.fish_cycle.id', $cycle->id)
            ->assertJsonPath('data.quantity', 25)
            ->assertJsonPath('data.cause', 'Kualitas air');

        $this->assertDatabaseHas('mortalities', [
            'fish_cycle_id' => $cycle->id,
            'quantity' => 25,
            'cause' => 'Kualitas air',
        ]);
    }

    public function test_can_show_mortality(): void
    {
        $mortality = Mortality::factory()->create();

        $response = $this->getJson(
            "/api/mortalities/{$mortality->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $mortality->id)
            ->assertJsonPath(
                'data.fish_cycle.id',
                $mortality->fish_cycle_id
            );
    }

    public function test_mortality_requires_valid_data(): void
    {
        $response = $this->postJson('/api/mortalities', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'fish_cycle_id',
                'mortality_date',
                'quantity',
            ]);
    }

    public function test_mortality_quantity_must_be_at_least_one(): void
    {
        $cycle = FishCycle::factory()->create([
            'initial_fish_count' => 1000,
        ]);

        $response = $this->postJson('/api/mortalities', [
            'fish_cycle_id' => $cycle->id,
            'mortality_date' => '2026-09-30',
            'quantity' => 0,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'quantity',
            ]);
    }

    public function test_mortality_cannot_exceed_available_fish(): void
    {
        $cycle = FishCycle::factory()->create([
            'initial_fish_count' => 100,
        ]);

        $response = $this->postJson('/api/mortalities', [
            'fish_cycle_id' => $cycle->id,
            'mortality_date' => '2026-09-30',
            'quantity' => 101,
        ]);

        $response
            ->assertStatus(500)
            ->assertJsonFragment([
                'message' =>
                'Jumlah ikan mati melebihi ikan yang tersedia. Ikan tersedia: 100 ekor.',
            ]);
    }

    public function test_mortality_cannot_exceed_remaining_fish_after_previous_mortality(): void
    {
        $cycle = FishCycle::factory()->create([
            'initial_fish_count' => 100,
        ]);

        Mortality::factory()->create([
            'fish_cycle_id' => $cycle->id,
            'quantity' => 70,
        ]);

        $response = $this->postJson('/api/mortalities', [
            'fish_cycle_id' => $cycle->id,
            'mortality_date' => '2026-09-30',
            'quantity' => 31,
        ]);

        $response
            ->assertStatus(500)
            ->assertJsonFragment([
                'message' =>
                'Jumlah ikan mati melebihi ikan yang tersedia. Ikan tersedia: 30 ekor.',
            ]);
    }

    public function test_can_create_multiple_mortalities_within_available_fish(): void
    {
        $cycle = FishCycle::factory()->create([
            'initial_fish_count' => 100,
        ]);

        Mortality::factory()->create([
            'fish_cycle_id' => $cycle->id,
            'quantity' => 30,
        ]);

        $response = $this->postJson('/api/mortalities', [
            'fish_cycle_id' => $cycle->id,
            'mortality_date' => '2026-09-30',
            'quantity' => 70,
            'cause' => 'Penyakit',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.quantity', 70);

        $this->assertDatabaseCount('mortalities', 2);
    }

    public function test_update_mortality_is_disabled(): void
    {
        $mortality = Mortality::factory()->create();

        $response = $this->putJson(
            "/api/mortalities/{$mortality->id}",
            [
                'fish_cycle_id' => $mortality->fish_cycle_id,
                'mortality_date' => '2026-09-30',
                'quantity' => 10,
                'cause' => 'Penyakit',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' =>
                'Update mortality akan kita aktifkan setelah mekanisme koreksi jumlah ikan selesai.',
            ]);
    }

    public function test_delete_mortality_is_disabled(): void
    {
        $mortality = Mortality::factory()->create();

        $response = $this->deleteJson(
            "/api/mortalities/{$mortality->id}"
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' =>
                'Penghapusan mortality akan kita aktifkan setelah mekanisme koreksi jumlah ikan selesai.',
            ]);

        $this->assertDatabaseHas('mortalities', [
            'id' => $mortality->id,
        ]);
    }

    public function test_show_returns_404_for_non_existing_mortality(): void
    {
        $response = $this->getJson('/api/mortalities/999999');

        $response->assertNotFound();
    }
}
