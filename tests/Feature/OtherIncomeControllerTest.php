<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\OtherIncome;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtherIncomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_other_income(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/other-incomes', [
                'income_date' => '2026-09-29',
                'category' => 'Bonus',
                'description' => 'Pendapatan tambahan',
                'amount' => 150000,
                'notes' => 'Pendapatan lain-lain',
            ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.category', 'Bonus')
            ->assertJsonPath('data.description', 'Pendapatan tambahan')
            ->assertJsonPath('data.amount', 150000);

        $this->assertDatabaseHas('other_incomes', [
            'category' => 'Bonus',
            'description' => 'Pendapatan tambahan',
            'amount' => 150000,
            'created_by' => $user->id,
        ]);
    }

    public function test_can_list_other_incomes(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        OtherIncome::create([
            'income_date' => '2026-09-29',
            'category' => 'Bonus',
            'description' => 'Pendapatan tambahan',
            'amount' => 150000,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/other-incomes');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_show_other_income(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $income = OtherIncome::create([
            'income_date' => '2026-09-29',
            'category' => 'Bonus',
            'description' => 'Pendapatan tambahan',
            'amount' => 150000,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/other-incomes/{$income->id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $income->id);
    }

    public function test_update_other_income_also_updates_cash_transaction(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/other-incomes', [
                'income_date' => '2026-09-29',
                'category' => 'Bonus',
                'description' => 'Pendapatan awal',
                'amount' => 150000,
                'notes' => 'Initial',
            ]);

        $response->assertSuccessful();

        $income = OtherIncome::query()->firstOrFail();

        $cashTransaction = CashTransaction::query()
            ->where('reference_id', $income->id)
            ->where('category', 'other_income')
            ->firstOrFail();

        $updateResponse = $this->actingAs($user)
            ->putJson("/api/other-incomes/{$income->id}", [
                'income_date' => '2026-09-30',
                'category' => 'Penjualan Lain',
                'description' => 'Pendapatan diperbarui',
                'amount' => 200000,
                'notes' => 'Updated',
            ]);

        $updateResponse->assertSuccessful()
            ->assertJsonPath('data.category', 'Penjualan Lain')
            ->assertJsonPath('data.description', 'Pendapatan diperbarui')
            ->assertJsonPath('data.amount', 200000);

        $this->assertDatabaseHas('other_incomes', [
            'id' => $income->id,
            'category' => 'Penjualan Lain',
            'description' => 'Pendapatan diperbarui',
            'amount' => 200000,
        ]);

        $this->assertSame(
            '2026-09-30',
            $income->refresh()->income_date->format('Y-m-d')
        );

        $this->assertDatabaseHas('cash_transactions', [
            'id' => $cashTransaction->id,
            'type' => 'in',
            'category' => 'other_income',
            'reference_id' => $income->id,
            'description' => 'Pendapatan diperbarui',
            'amount' => 200000,
        ]);

        $this->assertSame(
            '2026-09-30',
            $cashTransaction->refresh()->transaction_date->format('Y-m-d')
        );
    }

    public function test_delete_other_income_also_deletes_cash_transaction(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/other-incomes', [
                'income_date' => '2026-09-29',
                'category' => 'Bonus',
                'description' => 'Pendapatan tambahan',
                'amount' => 150000,
            ]);

        $response->assertSuccessful();

        $income = OtherIncome::query()->firstOrFail();

        $cashTransaction = CashTransaction::query()
            ->where('reference_id', $income->id)
            ->where('category', 'other_income')
            ->firstOrFail();

        $deleteResponse = $this->actingAs($user)
            ->deleteJson("/api/other-incomes/{$income->id}");

        $deleteResponse->assertSuccessful()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('other_incomes', [
            'id' => $income->id,
        ]);

        $this->assertDatabaseMissing('cash_transactions', [
            'id' => $cashTransaction->id,
        ]);
    }

    public function test_amount_must_be_greater_than_zero(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/other-incomes', [
                'income_date' => '2026-09-29',
                'category' => 'Bonus',
                'description' => 'Pendapatan tambahan',
                'amount' => 0,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'amount',
            ]);
    }

    public function test_required_fields_are_validated(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/other-incomes', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'income_date',
                'category',
                'description',
                'amount',
            ]);
    }

    public function test_creating_other_income_creates_cash_income(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/other-incomes', [
                'income_date' => '2026-09-29',
                'category' => 'Bonus',
                'description' => 'Pendapatan tambahan',
                'amount' => 150000,
                'notes' => 'Pendapatan lain-lain',
            ]);

        $response->assertSuccessful();

        $income = OtherIncome::query()->firstOrFail();

        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'in',
            'category' => 'other_income',
            'reference_type' => $income->getMorphClass(),
            'reference_id' => $income->id,
            'description' => 'Pendapatan tambahan',
            'amount' => 150000,
            'created_by' => $user->id,
        ]);
    }
}
