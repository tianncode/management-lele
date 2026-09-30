<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($this->user, 'sanctum');

        $this->category = ProductCategory::factory()->create();
    }

    public function test_can_list_products(): void
    {
        Product::factory()
            ->count(3)
            ->create([
                'category_id' => $this->category->id,
            ]);

        $response = $this->getJson('/api/products');

        $response
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'category_id',
                        'code',
                        'name',
                        'unit',
                        'current_stock',
                        'minimum_stock',
                        'average_price',
                        'is_active',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_can_create_product(): void
    {
        $response = $this->postJson('/api/products', [
            'category_id' => $this->category->id,
            'code' => 'PCK-001',
            'name' => 'Pakan Lele Premium',
            'unit' => 'kg',
            'current_stock' => 100,
            'minimum_stock' => 20,
            'average_price' => 15000,
            'is_active' => true,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'category_id',
                    'code',
                    'name',
                    'unit',
                    'current_stock',
                    'minimum_stock',
                    'average_price',
                    'is_active',
                ],
            ])
            ->assertJsonPath('data.name', 'Pakan Lele Premium')
            ->assertJsonPath('data.code', 'PCK-001');

        $this->assertDatabaseHas('products', [
            'code' => 'PCK-001',
            'name' => 'Pakan Lele Premium',
            'category_id' => $this->category->id,
        ]);
    }

    public function test_can_show_product(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Pakan Lele Premium',
        ]);

        $response = $this->getJson(
            "/api/products/{$product->id}"
        );

        $response
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'category_id',
                    'code',
                    'name',
                    'unit',
                    'current_stock',
                    'minimum_stock',
                    'average_price',
                    'is_active',
                ],
            ])
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', 'Pakan Lele Premium');
    }

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'code' => 'PCK-001',
            'name' => 'Pakan Lele',
        ]);

        $response = $this->putJson(
            "/api/products/{$product->id}",
            [
                'category_id' => $this->category->id,
                'code' => 'PCK-001',
                'name' => 'Pakan Lele Premium',
                'unit' => 'kg',
                'current_stock' => 150,
                'minimum_stock' => 25,
                'average_price' => 16000,
                'is_active' => true,
            ]
        );

        $response
            ->assertSuccessful()
            ->assertJsonPath(
                'data.name',
                'Pakan Lele Premium'
            );

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Pakan Lele Premium',
            'current_stock' => 150,
            'minimum_stock' => 25,
        ]);
    }

    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
        ]);

        $response = $this->deleteJson(
            "/api/products/{$product->id}"
        );

        $response
            ->assertSuccessful()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_category_id_is_required(): void
    {
        $response = $this->postJson('/api/products', [
            'code' => 'PCK-001',
            'name' => 'Pakan Lele',
            'unit' => 'kg',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'category_id',
            ]);
    }

    public function test_category_must_exist(): void
    {
        $response = $this->postJson('/api/products', [
            'category_id' => 999999,
            'code' => 'PCK-001',
            'name' => 'Pakan Lele',
            'unit' => 'kg',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'category_id',
            ]);
    }

    public function test_code_is_required(): void
    {
        $response = $this->postJson('/api/products', [
            'category_id' => $this->category->id,
            'name' => 'Pakan Lele',
            'unit' => 'kg',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'code',
            ]);
    }

    public function test_code_must_be_unique(): void
    {
        Product::factory()->create([
            'category_id' => $this->category->id,
            'code' => 'PCK-001',
        ]);

        $response = $this->postJson('/api/products', [
            'category_id' => $this->category->id,
            'code' => 'PCK-001',
            'name' => 'Pakan Baru',
            'unit' => 'kg',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'code',
            ]);
    }

    public function test_product_name_is_required(): void
    {
        $response = $this->postJson('/api/products', [
            'category_id' => $this->category->id,
            'code' => 'PCK-001',
            'unit' => 'kg',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
            ]);
    }

    public function test_unit_is_required(): void
    {
        $response = $this->postJson('/api/products', [
            'category_id' => $this->category->id,
            'code' => 'PCK-001',
            'name' => 'Pakan Lele',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'unit',
            ]);
    }

    public function test_show_returns_404_for_non_existing_product(): void
    {
        $response = $this->getJson('/api/products/999999');

        $response->assertNotFound();
    }

    public function test_update_returns_404_for_non_existing_product(): void
    {
        $response = $this->putJson(
            '/api/products/999999',
            [
                'category_id' => $this->category->id,
                'code' => 'PCK-001',
                'name' => 'Produk Baru',
                'unit' => 'kg',
            ]
        );

        $response->assertNotFound();
    }

    public function test_delete_returns_404_for_non_existing_product(): void
    {
        $response = $this->deleteJson(
            '/api/products/999999'
        );

        $response->assertNotFound();
    }
}
