<?php

namespace Tests\Feature;

use App\Enums\StatusWithdrawEnum;
use App\Jobs\CreateWithdrawJob;
use App\Models\Subacquirer;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CompleteWithdrawFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_withdraw_flow_with_subadqa(): void
    {
        Http::fake([
            'https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io/withdraw' => Http::response([
                'success' => true,
                'message' => 'Saque criado com sucesso',
                'transaction_id' => 'TXN456789',
                'withdraw_id' => 'WD123456',
                'status' => 'PENDING',
                'amount' => 500.00,
            ], 200),
        ]);

        $subacquirer = Subacquirer::create([
            'id' => 1,
            'name' => 'SubadqA',
            'base_url' => 'https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io',
            'active' => true,
        ]);

        $user = User::factory()->create([
            'subacquirer_id' => $subacquirer->id,
        ]);

        $payload = [
            'user_id' => $user->id,
            'amount' => 500.00,
            'bank_account' => [
                'bank' => '001',
                'agency' => '1234',
                'account' => '56789-0',
            ],
            'metadata' => ['type' => 'withdrawal'],
        ];

        $response = $this->postJson('/api/withdraw', $payload);
        $response->assertStatus(204);

        $this->artisan('queue:work', ['--once' => true, '--stop-when-empty' => true]);

        $withdraw = Withdraw::where('user_id', $user->id)->first();
        $this->assertNotNull($withdraw);
        $this->assertEquals('TXN456789', $withdraw->external_id);
        $this->assertEquals('WD123456', $withdraw->withdraw_id);
        $this->assertEquals(StatusWithdrawEnum::PROCESSING, $withdraw->status);
        $this->assertEquals(500.00, (float) $withdraw->amount);

        $webhookPayload = [
            'event' => 'withdraw_completed',
            'withdraw_id' => 'WD123456',
            'transaction_id' => 'TXN456789',
            'status' => 'SUCCESS',
            'amount' => 500.00,
            'requested_at' => '2025-11-13T13:10:00Z',
            'completed_at' => '2025-11-13T13:12:30Z',
            'metadata' => [
                'source' => 'SubadqA',
                'destination_bank' => 'Itaú',
            ],
        ];

        $webhookResponse = $this->postJson('/api/webhook/withdraw', $webhookPayload);
        $webhookResponse->assertStatus(204);

        $withdraw->refresh();
        $this->assertEquals(StatusWithdrawEnum::SUCCESS, $withdraw->status);
    }

    public function test_complete_withdraw_flow_with_subadqb(): void
    {
        Http::fake([
            'https://ef8513c8-fd99-4081-8963-573cd135e133.mock.pstmn.io/withdraw' => Http::response([
                'success' => true,
                'transaction_id' => 'WDX54321',
                'status' => 'PROCESSING',
                'amount' => 850.00,
            ], 200),
        ]);

        Subacquirer::create([
            'id' => 1,
            'name' => 'SubadqA',
            'base_url' => 'https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io',
            'active' => true,
        ]);

        $subacquirer = Subacquirer::create([
            'id' => 2,
            'name' => 'SubadqB',
            'base_url' => 'https://ef8513c8-fd99-4081-8963-573cd135e133.mock.pstmn.io',
            'active' => true,
        ]);

        $user = User::factory()->create([
            'subacquirer_id' => $subacquirer->id,
        ]);

        $payload = [
            'user_id' => $user->id,
            'amount' => 850.00,
            'bank_account' => [
                'bank' => 'Nubank',
                'agency' => '0001',
                'account' => '1234567-8',
            ],
        ];

        $response = $this->postJson('/api/withdraw', $payload);
        $response->assertStatus(204);

        $this->artisan('queue:work', ['--once' => true, '--stop-when-empty' => true]);

        $withdraw = Withdraw::where('user_id', $user->id)->first();
        $this->assertNotNull($withdraw);
        $this->assertEquals('WDX54321', $withdraw->external_id);
        $this->assertEquals(StatusWithdrawEnum::PROCESSING, $withdraw->status);

        $webhookPayload = [
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

        $webhookResponse = $this->postJson('/api/webhook/withdraw', $webhookPayload);
        $webhookResponse->assertStatus(204);

        $withdraw->refresh();
        $this->assertEquals(StatusWithdrawEnum::DONE, $withdraw->status);
    }

    public function test_complete_withdraw_flow_with_failure(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Insufficient balance'], 400),
        ]);

        $subacquirer = Subacquirer::create([
            'id' => 1,
            'name' => 'SubadqA',
            'base_url' => 'https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io',
            'active' => true,
        ]);

        $user = User::factory()->create([
            'subacquirer_id' => $subacquirer->id,
        ]);

        $job = new CreateWithdrawJob($user->id, $subacquirer->id, [
            'amount' => 1000.00,
            'bank_account' => [
                'bank' => '001',
                'agency' => '1234',
                'account' => '56789-0',
            ],
            'metadata' => [],
        ]);

        try {
            $job->handle();
        } catch (\Exception $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception ?? null);

        $job->failed($exception);

        $failedWithdraw = Withdraw::where('user_id', $user->id)
            ->where('status', StatusWithdrawEnum::FAILED)
            ->first();

        $this->assertNotNull($failedWithdraw);
        $this->assertEquals(StatusWithdrawEnum::FAILED, $failedWithdraw->status);
        $this->assertArrayHasKey('error', $failedWithdraw->metadata);
    }

    public function test_multiple_withdraws_for_same_user(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'transaction_id' => 'TXN' . rand(1000, 9999),
                'withdraw_id' => 'WD' . rand(1000, 9999),
                'status' => 'PENDING',
            ], 200),
        ]);

        $subacquirer = Subacquirer::create([
            'id' => 1,
            'name' => 'SubadqA',
            'base_url' => 'https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io',
            'active' => true,
        ]);

        $user = User::factory()->create([
            'subacquirer_id' => $subacquirer->id,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $payload = [
                'user_id' => $user->id,
                'amount' => 100.00 * $i,
                'bank_account' => [
                    'bank' => '001',
                    'agency' => '1234',
                    'account' => '56789-0',
                ],
            ];

            $response = $this->postJson('/api/withdraw', $payload);
            $response->assertStatus(204);
        }

        Queue::fake();

        $this->assertEquals(3, Withdraw::where('user_id', $user->id)->count());
    }
}

