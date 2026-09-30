<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_cash_transactions(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        CashTransaction::create([
            'transaction_date' => '2026-09-30',
            'type' => 'in',
            'category' => 'sale',
            'description' => 'Pembayaran penjualan',
            'amount' => 500000,
        ]);

        CashTransaction::create([
            'transaction_date' => '2026-09-29',
            'type' => 'out',
            'category' => 'purchase',
            'description' => 'Pembayaran pembelian',
            'amount' => 250000,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/cash-transactions');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'in')
            ->assertJsonPath('data.0.amount', 500000)
            ->assertJsonPath('data.1.type', 'out')
            ->assertJsonPath('data.1.amount', 250000);
    }

    public function test_can_create_cash_income(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/cash-transactions', [
                'transaction_date' => '2026-09-30',
                'type' => 'in',
                'category' => 'other_income',
                'description' => 'Pendapatan lainnya',
                'amount' => 300000,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.type', 'in')
            ->assertJsonPath('data.category', 'other_income')
            ->assertJsonPath('data.amount', 300000);

        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'in',
            'category' => 'other_income',
            'description' => 'Pendapatan lainnya',
            'amount' => 300000,
        ]);
    }

    public function test_can_create_cash_expense(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/cash-transactions', [
                'transaction_date' => '2026-09-30',
                'type' => 'out',
                'category' => 'operational',
                'description' => 'Biaya operasional',
                'amount' => 150000,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.type', 'out')
            ->assertJsonPath('data.amount', 150000);

        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'out',
            'category' => 'operational',
            'description' => 'Biaya operasional',
            'amount' => 150000,
        ]);
    }

    public function test_cash_transaction_rejects_invalid_amount(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/cash-transactions', [
                'transaction_date' => '2026-09-30',
                'type' => 'in',
                'category' => 'sale',
                'description' => 'Invalid transaction',
                'amount' => 0,
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount(
            'cash_transactions',
            0
        );
    }

    public function test_can_get_cash_balance(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        CashTransaction::create([
            'transaction_date' => '2026-09-30',
            'type' => 'in',
            'category' => 'sale',
            'description' => 'Penjualan',
            'amount' => 1500000,
        ]);

        CashTransaction::create([
            'transaction_date' => '2026-09-30',
            'type' => 'in',
            'category' => 'other_income',
            'description' => 'Pendapatan lainnya',
            'amount' => 500000,
        ]);

        CashTransaction::create([
            'transaction_date' => '2026-09-30',
            'type' => 'out',
            'category' => 'purchase',
            'description' => 'Pembelian',
            'amount' => 750000,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/cash-transactions/balance');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.income', 2000000)
            ->assertJsonPath('data.expense', 750000)
            ->assertJsonPath('data.balance', 1250000);
    }

    public function test_can_show_cash_transaction(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $transaction = CashTransaction::create([
            'transaction_date' => '2026-09-30',
            'type' => 'in',
            'category' => 'sale',
            'description' => 'Pembayaran penjualan',
            'amount' => 500000,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/cash-transactions/{$transaction->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $transaction->id)
            ->assertJsonPath('data.amount', 500000);
    }

    public function test_cash_transaction_update_is_disabled(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $transaction = CashTransaction::create([
            'transaction_date' => '2026-09-30',
            'type' => 'out',
            'category' => 'operational',
            'description' => 'Biaya lama',
            'amount' => 100000,
        ]);

        $response = $this->actingAs($user)
            ->putJson(
                "/api/cash-transactions/{$transaction->id}",
                [
                    'transaction_date' => '2026-09-30',
                    'type' => 'out',
                    'category' => 'operational',
                    'description' => 'Biaya baru',
                    'amount' => 125000,
                ]
            );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Update transaksi kas dinonaktifkan. Koreksi transaksi harus dilakukan melalui transaksi sumber.',
            ]);

        $this->assertDatabaseHas('cash_transactions', [
            'id' => $transaction->id,
            'description' => 'Biaya lama',
            'amount' => 100000,
        ]);
    }

    public function test_cash_transaction_delete_is_disabled(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $transaction = CashTransaction::create([
            'transaction_date' => '2026-09-30',
            'type' => 'out',
            'category' => 'operational',
            'description' => 'Biaya yang tidak boleh dihapus',
            'amount' => 100000,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson(
                "/api/cash-transactions/{$transaction->id}"
            );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Penghapusan transaksi kas dinonaktifkan. Hapus atau koreksi transaksi melalui transaksi sumber.',
            ]);

        $this->assertDatabaseHas('cash_transactions', [
            'id' => $transaction->id,
        ]);
    }
}
