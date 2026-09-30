<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashTransactionApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($this->user, 'sanctum');
    }

    public function test_can_list_cash_transactions(): void
    {
        CashTransaction::create([
            'transaction_date' => '2026-09-30',
            'type' => 'in',
            'category' => 'manual',
            'description' => 'Modal awal',
            'amount' => 1000000,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/cash-transactions');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_create_cash_income(): void
    {
        $response = $this->postJson('/api/cash-transactions', [
            'transaction_date' => '2026-09-30',
            'type' => 'in',
            'category' => 'modal',
            'description' => 'Modal awal usaha',
            'amount' => 1000000,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'in')
            ->assertJsonPath('data.category', 'modal')
            ->assertJsonPath('data.amount', 1000000);

        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'in',
            'category' => 'modal',
            'description' => 'Modal awal usaha',
            'amount' => 1000000,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_can_create_cash_expense(): void
    {
        $response = $this->postJson('/api/cash-transactions', [
            'transaction_date' => '2026-09-30',
            'type' => 'out',
            'category' => 'operational',
            'description' => 'Biaya operasional',
            'amount' => 250000,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'out')
            ->assertJsonPath('data.category', 'operational')
            ->assertJsonPath('data.amount', 250000);

        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'out',
            'category' => 'operational',
            'description' => 'Biaya operasional',
            'amount' => 250000,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_cash_balance_is_calculated_correctly(): void
    {
        CashTransaction::create([
            'transaction_date' => '2026-09-30',
            'type' => 'in',
            'category' => 'modal',
            'description' => 'Modal',
            'amount' => 1000000,
            'created_by' => $this->user->id,
        ]);

        CashTransaction::create([
            'transaction_date' => '2026-09-30',
            'type' => 'in',
            'category' => 'sale',
            'description' => 'Penjualan',
            'amount' => 500000,
            'created_by' => $this->user->id,
        ]);

        CashTransaction::create([
            'transaction_date' => '2026-09-30',
            'type' => 'out',
            'category' => 'expense',
            'description' => 'Pengeluaran',
            'amount' => 300000,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson(
            '/api/cash-transactions/balance'
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.income', 1500000)
            ->assertJsonPath('data.expense', 300000)
            ->assertJsonPath('data.balance', 1200000);
    }

    public function test_cash_balance_returns_zero_when_no_transactions(): void
    {
        $response = $this->getJson(
            '/api/cash-transactions/balance'
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.income', 0)
            ->assertJsonPath('data.expense', 0)
            ->assertJsonPath('data.balance', 0);
    }

    public function test_cash_transaction_amount_must_be_greater_than_zero(): void
    {
        $response = $this->postJson('/api/cash-transactions', [
            'transaction_date' => '2026-09-30',
            'type' => 'in',
            'category' => 'modal',
            'description' => 'Invalid',
            'amount' => 0,
        ]);

        $response->assertStatus(422);
    }

    public function test_cash_transaction_rejects_negative_amount(): void
    {
        $response = $this->postJson('/api/cash-transactions', [
            'transaction_date' => '2026-09-30',
            'type' => 'in',
            'category' => 'modal',
            'description' => 'Invalid',
            'amount' => -100000,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');
    }

    public function test_cash_transaction_type_must_be_valid(): void
    {
        $response = $this->postJson('/api/cash-transactions', [
            'transaction_date' => '2026-09-30',
            'type' => 'invalid',
            'category' => 'modal',
            'description' => 'Invalid',
            'amount' => 100000,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');
    }

    public function test_cash_transaction_requires_valid_data(): void
    {
        $response = $this->postJson(
            '/api/cash-transactions',
            []
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'transaction_date',
                'type',
                'category',
                'description',
                'amount',
            ]);
    }

    public function test_can_show_cash_transaction(): void
    {
        $transaction = CashTransaction::create([
            'transaction_date' => '2026-09-30',
            'type' => 'in',
            'category' => 'modal',
            'description' => 'Modal awal',
            'amount' => 1000000,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson(
            "/api/cash-transactions/{$transaction->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $transaction->id)
            ->assertJsonPath('data.amount', 1000000);
    }

    public function test_update_cash_transaction_is_disabled(): void
    {
        $transaction = CashTransaction::create([
            'transaction_date' => '2026-09-30',
            'type' => 'in',
            'category' => 'modal',
            'description' => 'Modal awal',
            'amount' => 1000000,
            'created_by' => $this->user->id,
        ]);

        $response = $this->putJson(
            "/api/cash-transactions/{$transaction->id}",
            [
                'amount' => 2000000,
            ]
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('cash_transactions', [
            'id' => $transaction->id,
            'amount' => 1000000,
        ]);
    }

    public function test_delete_cash_transaction_is_disabled(): void
    {
        $transaction = CashTransaction::create([
            'transaction_date' => '2026-09-30',
            'type' => 'in',
            'category' => 'modal',
            'description' => 'Modal awal',
            'amount' => 1000000,
            'created_by' => $this->user->id,
        ]);

        $response = $this->deleteJson(
            "/api/cash-transactions/{$transaction->id}"
        );

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('cash_transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_show_returns_404_for_non_existing_transaction(): void
    {
        $response = $this->getJson(
            '/api/cash-transactions/999999'
        );

        $response->assertNotFound();
    }
}
