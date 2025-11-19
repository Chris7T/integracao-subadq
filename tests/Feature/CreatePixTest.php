<?php

namespace Tests\Feature;

use App\Jobs\CreatePixJob;
use App\Models\Subacquirer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreatePixTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_pix_transaction_successfully(): void
    {
        Queue::fake();

        $subacquirer = Subacquirer::factory()->create();
        $user = User::factory()->create(['subacquirer_id' => $subacquirer->id]);

        $payload = [
            'user_id' => $user->id,
            'amount' => 100.50,
            'payer_name' => 'John Doe',
            'payer_cpf' => '12345678900',
            'metadata' => ['key' => 'value'],
        ];

        $response = $this->postJson('/api/pix', $payload);

        $response->assertStatus(204);
        Queue::assertPushed(CreatePixJob::class, function ($job) use ($user, $subacquirer, $payload) {
            return $job->userId === $user->id
                && $job->subacquirerId === $subacquirer->id
                && $job->data['amount'] === $payload['amount'];
        });
    }

    public function test_returns_404_when_user_not_found(): void
    {
        Queue::fake();

        $payload = [
            'user_id' => 999,
            'amount' => 100.50,
        ];

        $response = $this->postJson('/api/pix', $payload);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'User not found',
            ]);
        Queue::assertNothingPushed();
    }

    public function test_validates_required_fields(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/pix', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'amount']);
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
        ];

        $response = $this->postJson('/api/pix', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
        Queue::assertNothingPushed();
    }

    public function test_validates_payer_cpf_format(): void
    {
        Queue::fake();

        $subacquirer = Subacquirer::factory()->create();
        $user = User::factory()->create(['subacquirer_id' => $subacquirer->id]);

        $payload = [
            'user_id' => $user->id,
            'amount' => 100.50,
            'payer_cpf' => 'invalid-cpf',
        ];

        $response = $this->postJson('/api/pix', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payer_cpf']);
        Queue::assertNothingPushed();
    }
}

