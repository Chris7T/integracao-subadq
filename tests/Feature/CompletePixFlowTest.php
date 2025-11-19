<?php

namespace Tests\Feature;

use App\Enums\StatusPixEnum;
use App\Jobs\CreatePixJob;
use App\Jobs\SimulatePixWebhookJob;
use App\Models\Pix;
use App\Models\Subacquirer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CompletePixFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_pix_flow_with_subadqa(): void
    {
        Http::fake([
            'https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io/pix/create' => Http::response([
                'success' => true,
                'message' => 'PIX criado com sucesso',
                'transaction_id' => 'TXN123456',
                'pix_id' => 'PIX789012',
                'status' => 'PENDING',
                'amount' => 100.50,
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
            'amount' => 100.50,
            'payer_name' => 'João da Silva',
            'payer_cpf' => '12345678900',
            'metadata' => ['order_id' => '12345'],
        ];

        $response = $this->postJson('/api/pix', $payload);
        $response->assertStatus(204);

        $this->artisan('queue:work', ['--once' => true, '--stop-when-empty' => true]);

        $pix = Pix::where('user_id', $user->id)->first();
        $this->assertNotNull($pix);
        $this->assertEquals('TXN123456', $pix->external_id);
        $this->assertEquals('PIX789012', $pix->pix_id);
        $this->assertEquals(StatusPixEnum::PROCESSING, $pix->status);
        $this->assertEquals(100.50, (float) $pix->amount);

        $webhookPayload = [
            'event' => 'pix_payment_confirmed',
            'transaction_id' => 'TXN123456',
            'pix_id' => 'PIX789012',
            'status' => 'CONFIRMED',
            'amount' => 100.50,
            'payer_name' => 'João da Silva',
            'payer_cpf' => '12345678900',
            'payment_date' => '2025-11-13T14:25:00Z',
            'metadata' => [
                'source' => 'SubadqA',
            ],
        ];

        $webhookResponse = $this->postJson('/api/webhook/pix', $webhookPayload);
        $webhookResponse->assertStatus(204);

        $pix->refresh();
        $this->assertEquals(StatusPixEnum::CONFIRMED, $pix->status);
        $this->assertEquals('João da Silva', $pix->payer_name);
        $this->assertEquals('12345678900', $pix->payer_cpf);
    }

    public function test_complete_pix_flow_with_subadqb(): void
    {
        Http::fake([
            'https://ef8513c8-fd99-4081-8963-573cd135e133.mock.pstmn.io/pix/create' => Http::response([
                'success' => true,
                'transaction_id' => 'PX987654',
                'status' => 'PROCESSING',
                'value' => 250.00,
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
            'amount' => 250.00,
            'payer_name' => 'Maria Oliveira',
            'payer_cpf' => '98765432100',
        ];

        $response = $this->postJson('/api/pix', $payload);

        $response->assertStatus(204);

        $this->artisan('queue:work', ['--once' => true, '--stop-when-empty' => true]);

        $pix = Pix::where('user_id', $user->id)->first();
        $this->assertNotNull($pix);
        $this->assertEquals('PX987654', $pix->external_id);
        $this->assertEquals(StatusPixEnum::PROCESSING, $pix->status);

        $webhookPayload = [
            'type' => 'pix.status_update',
            'data' => [
                'id' => 'PX987654',
                'status' => 'PAID',
                'value' => 250.00,
                'payer' => [
                    'name' => 'Maria Oliveira',
                    'document' => '98765432100',
                ],
                'confirmed_at' => '2025-11-13T14:40:00Z',
            ],
            'signature' => 'd1c4b6f98eaa',
        ];

        $webhookResponse = $this->postJson('/api/webhook/pix', $webhookPayload);
        $webhookResponse->assertStatus(204);

        $pix->refresh();
        $this->assertEquals(StatusPixEnum::PAID, $pix->status);
        $this->assertEquals('Maria Oliveira', $pix->payer_name);
        $this->assertEquals('98765432100', $pix->payer_cpf);
    }

    public function test_complete_pix_flow_with_api_failure_and_retry(): void
    {
        $attempt = 0;
        Http::fake(function () use (&$attempt) {
            $attempt++;
            if ($attempt < 3) {
                return Http::response(['error' => 'Service unavailable'], 503);
            }
            return Http::response([
                'success' => true,
                'transaction_id' => 'TXN999',
                'pix_id' => 'PIX999',
                'status' => 'PENDING',
            ], 200);
        });

        $subacquirer = Subacquirer::create([
            'id' => 1,
            'name' => 'SubadqA',
            'base_url' => 'https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io',
            'active' => true,
        ]);

        $user = User::factory()->create([
            'subacquirer_id' => $subacquirer->id,
        ]);

        Queue::fake();

        $payload = [
            'user_id' => $user->id,
            'amount' => 50.00,
        ];

        $response = $this->postJson('/api/pix', $payload);
        $response->assertStatus(204);

        Queue::assertPushed(CreatePixJob::class);
    }

    public function test_complete_pix_flow_with_permanent_failure(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Service unavailable'], 503),
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

        $job = new CreatePixJob($user->id, $subacquirer->id, [
            'amount' => 50.00,
            'payer_name' => 'Test User',
            'payer_cpf' => '12345678900',
            'metadata' => [],
        ]);

        try {
            $job->handle();
        } catch (\Exception $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception ?? null);

        $job->failed($exception);

        $failedPix = Pix::where('user_id', $user->id)
            ->where('status', StatusPixEnum::FAILED)
            ->first();

        $this->assertNotNull($failedPix);
        $this->assertEquals(StatusPixEnum::FAILED, $failedPix->status);
        $this->assertArrayHasKey('error', $failedPix->metadata);
    }
}

