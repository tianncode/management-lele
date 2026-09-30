<?php

namespace Tests\Feature;

use App\Models\Feeding;
use App\Models\FishCycle;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedingApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->actingAs($this->user, 'sanctum');
    }

    public function test_can_list_feedings(): void
    {
        Feeding::factory()
            ->count(3)
            ->create();

        $response = $this->getJson('/api/feedings');

        $response
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'fish_cycle',
                        'product',
                        'feeding_date',
                        'feeding_time',
                        'quantity',
                        'unit_price',
                        'total_cost',
                        'method',
                        'notes',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_can_create_feeding(): void
    {
        $feeding = Feeding::factory()->make();

        $response = $this->postJson('/api/feedings', [
            'fish_cycle_id' => $feeding->fish_cycle_id,
            'product_id' => $feeding->product_id,
            'feeding_date' => '2026-09-30',
            'feeding_time' => '08:00',
            'quantity' => 10,
            'unit_price' => 12000,
            'notes' => 'Pemberian pakan pagi.',
        ]);

        $response
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'fish_cycle',
                    'product',
                    'feeding_date',
                    'feeding_time',
                    'quantity',
                    'unit_price',
                    'total_cost',
                    'method',
                    'notes',
                ],
            ]);

        $this->assertDatabaseHas('feedings', [
            'fish_cycle_id' => $feeding->fish_cycle_id,
            'product_id' => $feeding->product_id,
        ]);
    }

    public function test_can_show_feeding(): void
    {
        $feeding = Feeding::factory()->create();

        $response = $this->getJson(
            "/api/feedings/{$feeding->id}"
        );

        $response
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'fish_cycle',
                    'product',
                    'feeding_date',
                    'feeding_time',
                    'quantity',
                    'unit_price',
                    'total_cost',
                    'method',
                    'notes',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', $feeding->id);
    }

    public function test_update_feeding_is_temporarily_disabled(): void
    {
        $feeding = Feeding::factory()->create();

        $response = $this->putJson(
            "/api/feedings/{$feeding->id}",
            [
                'fish_cycle_id' => $feeding->fish_cycle_id,
                'product_id' => $feeding->product_id,
                'feeding_date' => '2026-09-30',
                'feeding_time' => '10:00',
                'quantity' => 20,
                'unit_price' => 15000,
                'notes' => 'Updated.',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Untuk menjaga integritas stok, perubahan data pakan akan kita implementasikan setelah Stock Movement Service selesai.',
            ]);
    }

    public function test_delete_feeding_is_temporarily_disabled(): void
    {
        $feeding = Feeding::factory()->create();

        $response = $this->deleteJson(
            "/api/feedings/{$feeding->id}"
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Penghapusan data pakan dinonaktifkan sementara karena harus mengembalikan stok secara otomatis.',
            ]);

        $this->assertDatabaseHas('feedings', [
            'id' => $feeding->id,
        ]);
    }

    public function test_fish_cycle_id_is_required(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/feedings', [
            'product_id' => $product->id,
            'feeding_date' => '2026-09-30',
            'quantity' => 10,
            'unit_price' => 12000,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'fish_cycle_id',
            ]);
    }

    public function test_fish_cycle_must_exist(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/feedings', [
            'fish_cycle_id' => 999999,
            'product_id' => $product->id,
            'feeding_date' => '2026-09-30',
            'quantity' => 10,
            'unit_price' => 12000,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'fish_cycle_id',
            ]);
    }

    public function test_product_id_is_required(): void
    {
        $cycle = FishCycle::factory()->create();

        $response = $this->postJson('/api/feedings', [
            'fish_cycle_id' => $cycle->id,
            'feeding_date' => '2026-09-30',
            'quantity' => 10,
            'unit_price' => 12000,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'product_id',
            ]);
    }

    public function test_product_must_exist(): void
    {
        $cycle = FishCycle::factory()->create();

        $response = $this->postJson('/api/feedings', [
            'fish_cycle_id' => $cycle->id,
            'product_id' => 999999,
            'feeding_date' => '2026-09-30',
            'quantity' => 10,
            'unit_price' => 12000,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'product_id',
            ]);
    }

    public function test_feeding_date_is_required(): void
    {
        $feeding = Feeding::factory()->make();

        $response = $this->postJson('/api/feedings', [
            'fish_cycle_id' => $feeding->fish_cycle_id,
            'product_id' => $feeding->product_id,
            'quantity' => 10,
            'unit_price' => 12000,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'feeding_date',
            ]);
    }

    public function test_quantity_must_be_greater_than_zero(): void
    {
        $feeding = Feeding::factory()->make();

        $response = $this->postJson('/api/feedings', [
            'fish_cycle_id' => $feeding->fish_cycle_id,
            'product_id' => $feeding->product_id,
            'feeding_date' => '2026-09-30',
            'quantity' => 0,
            'unit_price' => 12000,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'quantity',
            ]);
    }

    public function test_unit_price_cannot_be_negative(): void
    {
        $feeding = Feeding::factory()->make();

        $response = $this->postJson('/api/feedings', [
            'fish_cycle_id' => $feeding->fish_cycle_id,
            'product_id' => $feeding->product_id,
            'feeding_date' => '2026-09-30',
            'quantity' => 10,
            'unit_price' => -1,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'unit_price',
            ]);
    }

    public function test_feeding_time_must_use_valid_format(): void
    {
        $feeding = Feeding::factory()->make();

        $response = $this->postJson('/api/feedings', [
            'fish_cycle_id' => $feeding->fish_cycle_id,
            'product_id' => $feeding->product_id,
            'feeding_date' => '2026-09-30',
            'feeding_time' => 'invalid-time',
            'quantity' => 10,
            'unit_price' => 12000,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'feeding_time',
            ]);
    }

    public function test_show_returns_404_for_non_existing_feeding(): void
    {
        $response = $this->getJson('/api/feedings/999999');

        $response->assertNotFound();
    }

    public function test_update_returns_404_for_non_existing_feeding(): void
    {
        $feeding = Feeding::factory()->make();

        $response = $this->putJson(
            '/api/feedings/999999',
            [
                'fish_cycle_id' => $feeding->fish_cycle_id,
                'product_id' => $feeding->product_id,
                'feeding_date' => '2026-09-30',
                'quantity' => 10,
                'unit_price' => 12000,
            ]
        );

        $response->assertNotFound();
    }

    public function test_delete_returns_404_for_non_existing_feeding(): void
    {
        $response = $this->deleteJson(
            '/api/feedings/999999'
        );

        $response->assertNotFound();
    }
}
