<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_protected_endpoint(): void
    {
        $response = $this->getJson('/api/products');

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_staff_can_access_normal_authenticated_endpoint(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/products');

        $response->assertOk();
    }

    public function test_staff_cannot_access_cash_transactions(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/cash-transactions');

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melakukan tindakan ini.',
            ]);
    }

    public function test_staff_cannot_access_other_incomes(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/other-incomes');

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melakukan tindakan ini.',
            ]);
    }

    public function test_staff_cannot_access_purchases(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/purchases');

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melakukan tindakan ini.',
            ]);
    }

    public function test_admin_can_access_cash_transactions(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/cash-transactions');

        $response->assertOk();
    }

    public function test_admin_can_access_other_incomes(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/other-incomes');

        $response->assertOk();
    }

    public function test_admin_can_access_purchases(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/purchases');

        $response->assertOk();
    }
}
