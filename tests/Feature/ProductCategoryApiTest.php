<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($this->user, 'sanctum');
    }

    public function test_can_list_product_categories(): void
    {
        ProductCategory::factory()->count(3)->create();

        $response = $this->getJson('/api/product-categories');

        $response
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'is_active',
                        'products_count',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_can_create_product_category(): void
    {
        $response = $this->postJson('/api/product-categories', [
            'name' => 'Pakan Lele',
            'description' => 'Kategori pakan ikan lele.',
            'is_active' => true,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'is_active',
                ],
            ])
            ->assertJsonPath('data.name', 'Pakan Lele');

        $this->assertDatabaseHas('product_categories', [
            'name' => 'Pakan Lele',
        ]);
    }

    public function test_can_show_product_category(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Pakan Lele',
        ]);

        $response = $this->getJson(
            "/api/product-categories/{$category->id}"
        );

        $response
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'is_active',
                    'products_count',
                ],
            ])
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', 'Pakan Lele');
    }

    public function test_can_update_product_category(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Pakan Lele',
        ]);

        $response = $this->putJson(
            "/api/product-categories/{$category->id}",
            [
                'name' => 'Pakan Lele Premium',
                'description' => 'Pakan premium.',
                'is_active' => true,
            ]
        );

        $response
            ->assertSuccessful()
            ->assertJsonPath(
                'data.name',
                'Pakan Lele Premium'
            );

        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => 'Pakan Lele Premium',
        ]);
    }

    public function test_can_delete_product_category_without_products(): void
    {
        $category = ProductCategory::factory()->create();

        $response = $this->deleteJson(
            "/api/product-categories/{$category->id}"
        );

        $response
            ->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'Kategori produk berhasil dihapus.',
            ]);

        $this->assertDatabaseMissing('product_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_cannot_delete_product_category_that_is_used_by_product(): void
    {
        $category = ProductCategory::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->deleteJson(
            "/api/product-categories/{$category->id}"
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh produk.',
            ]);

        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_name_is_required_when_creating_category(): void
    {
        $response = $this->postJson('/api/product-categories', [
            'description' => 'Tanpa nama.',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
            ]);
    }

    public function test_name_must_be_unique_when_creating_category(): void
    {
        ProductCategory::factory()->create([
            'name' => 'Pakan Lele',
        ]);

        $response = $this->postJson('/api/product-categories', [
            'name' => 'Pakan Lele',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
            ]);
    }

    public function test_category_name_can_remain_unchanged_when_updating(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Pakan Lele',
        ]);

        $response = $this->putJson(
            "/api/product-categories/{$category->id}",
            [
                'name' => 'Pakan Lele',
                'description' => 'Updated description.',
                'is_active' => true,
            ]
        );

        $response
            ->assertSuccessful()
            ->assertJsonPath('data.name', 'Pakan Lele');

        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => 'Pakan Lele',
            'description' => 'Updated description.',
        ]);
    }

    public function test_show_returns_404_for_non_existing_category(): void
    {
        $response = $this->getJson('/api/product-categories/999999');

        $response->assertNotFound();
    }

    public function test_update_returns_404_for_non_existing_category(): void
    {
        $response = $this->putJson(
            '/api/product-categories/999999',
            [
                'name' => 'Kategori Baru',
            ]
        );

        $response->assertNotFound();
    }

    public function test_delete_returns_404_for_non_existing_category(): void
    {
        $response = $this->deleteJson(
            '/api/product-categories/999999'
        );

        $response->assertNotFound();
    }
}
