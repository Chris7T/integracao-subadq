<?php

namespace Tests\Feature;

use App\Enums\StatusWithdrawEnum;
use App\Models\Subacquirer;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_process_withdraw_webhook_successfully_with_transaction_id(): void
    {
        $subacquirer = Subacquirer::factory()->create([
            'id' => 1,
            'name' => 'SubadqA',
        ]);
        $user = User::factory()->create(['subacquirer_id' => $subacquirer->id]);
        
        $withdraw = Withdraw::create([
            'user_id' => $user->id,
            'subacquirer_id' => $subacquirer->id,
            'external_id' => 'T987654321',
            'amount' => 500.00,
            'status' => StatusWithdrawEnum::PENDING,
        ]);

        $payload = [
            'event' => 'withdraw_completed',
            'withdraw_id' => 'WD123456789',
            'transaction_id' => 'T987654321',
            'status' => 'SUCCESS',
            'amount' => 500.00,
            'requested_at' => '2025-11-13T13:10:00Z',
            'completed_at' => '2025-11-13T13:12:30Z',
            'metadata' => [
                'source' => 'SubadqA',
                'destination_bank' => 'Itaú',
            ],
        ];

        $response = $this->postJson('/api/webhook/withdraw', $payload);

        $response->assertStatus(204);
        
        $withdraw->refresh();
        $this->assertEquals(StatusWithdrawEnum::SUCCESS, $withdraw->status);
    }

    public function test_can_process_withdraw_webhook_with_data_id(): void
    {
        $subacquirer = Subacquirer::factory()->create([
            'id' => 2,
            'name' => 'SubadqB',
        ]);
        $user = User::factory()->create(['subacquirer_id' => $subacquirer->id]);
        
        $withdraw = Withdraw::create([
            'user_id' => $user->id,
            'subacquirer_id' => $subacquirer->id,
            'external_id' => 'WDX54321',
            'amount' => 850.00,
            'status' => StatusWithdrawEnum::PENDING,
        ]);

        $payload = [
            'type' => 'withdraw.status_update',
            'data' => [
                'id' => 'WDX54321',
                'status' => 'DONE',
                'amount' => 850.00,
                'bank_account' => [
                    'bank' => 'Nubank',
                    'agency' => '0001',
                    'account' => '1234567-8',
                ],
                'processed_at' => '2025-11-13T13:45:10Z',
            ],
            'signature' => 'aabbccddeeff112233',
        ];

        $response = $this->postJson('/api/webhook/withdraw', $payload);

        $response->assertStatus(204);
        
        $withdraw->refresh();
        $this->assertEquals(StatusWithdrawEnum::DONE, $withdraw->status);
    }

    public function test_returns_400_when_payload_is_invalid(): void
    {
        $payload = [];

        $response = $this->postJson('/api/webhook/withdraw', $payload);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Invalid payload: transaction_id or data.id is required',
            ]);
    }

    public function test_returns_404_when_withdraw_not_found(): void
    {
        $payload = [
            'transaction_id' => 'non_existent_id',
        ];

        $response = $this->postJson('/api/webhook/withdraw', $payload);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Withdraw not found',
            ]);
    }
}

