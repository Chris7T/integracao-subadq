<?php

namespace Tests\Feature;

use App\Jobs\CreateWithdrawJob;
use App\Models\Subacquirer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreateWithdrawTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_withdraw_request_successfully(): void
    {
        Queue::fake();

        $subacquirer = Subacquirer::factory()->create();
        $user = User::factory()->create(['subacquirer_id' => $subacquirer->id]);

        $payload = [
            'user_id' => $user->id,
            'amount' => 500.00,
            'bank_account' => [
                'bank' => '001',
                'agency' => '1234',
                'account' => '56789-0',
            ],
            'metadata' => ['key' => 'value'],
        ];

        $response = $this->postJson('/api/withdraw', $payload);

        $response->assertStatus(204);
        Queue::assertPushed(CreateWithdrawJob::class, function ($job) use ($user, $subacquirer) {
            return $job->userId === $user->id
                && $job->subacquirerId === $subacquirer->id;
        });
    }

    public function test_returns_404_when_user_not_found(): void
    {
        Queue::fake();

        $payload = [
            'user_id' => 999,
            'amount' => 500.00,
            'bank_account' => [
                'bank' => '001',
                'agency' => '1234',
                'account' => '56789-0',
            ],
        ];

        $response = $this->postJson('/api/withdraw', $payload);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'User not found',
            ]);
        Queue::assertNothingPushed();
    }

    public function test_validates_required_fields(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/withdraw', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'amount', 'bank_account']);
        Queue::assertNothingPushed();
    }

    public function test_validates_bank_account_structure(): void
    {
        Queue::fake();

        $subacquirer = Subacquirer::factory()->create();
        $user = User::factory()->create(['subacquirer_id' => $subacquirer->id]);

        $payload = [
            'user_id' => $user->id,
            'amount' => 500.00,
            'bank_account' => [],
        ];

        $response = $this->postJson('/api/withdraw', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bank_account.bank', 'bank_account.agency', 'bank_account.account']);
        Queue::assertNothingPushed();
    }

    public function test_validates_amount_minimum(): void
    {
        Queue::fake();

        $subacquirer = Subacquirer::factory()->create();
        $user = User::factory()->create(['subacquirer_id' => $subacquirer->id]);

        $payload = [
            'user_id' => $user->id,
            'amount' => 0,
            'bank_account' => [
                'bank' => '001',
                'agency' => '1234',
                'account' => '56789-0',
            ],
        ];

        $response = $this->postJson('/api/withdraw', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
        Queue::assertNothingPushed();
    }
}

