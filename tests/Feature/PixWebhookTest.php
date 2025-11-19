<?php

namespace Tests\Feature;

use App\Enums\StatusPixEnum;
use App\Models\Pix;
use App\Models\Subacquirer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PixWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_process_pix_webhook_successfully_with_transaction_id(): void
    {
        $subacquirer = Subacquirer::factory()->create([
            'id' => 1,
            'name' => 'SubadqA',
        ]);
        $user = User::factory()->create(['subacquirer_id' => $subacquirer->id]);
        
        $pix = Pix::create([
            'user_id' => $user->id,
            'subacquirer_id' => $subacquirer->id,
            'external_id' => 'f1a2b3c4d5e6',
            'amount' => 125.50,
            'status' => StatusPixEnum::PENDING,
        ]);

        $payload = [
            'event' => 'pix_payment_confirmed',
            'transaction_id' => 'f1a2b3c4d5e6',
            'pix_id' => 'PIX123456789',
            'status' => 'CONFIRMED',
            'amount' => 125.50,
            'payer_name' => 'João da Silva',
            'payer_cpf' => '12345678900',
            'payment_date' => '2025-11-13T14:25:00Z',
            'metadata' => [
                'source' => 'SubadqA',
                'environment' => 'sandbox',
            ],
        ];

        $response = $this->postJson('/api/webhook/pix', $payload);
        $response->assertStatus(204);
        
        $pix->refresh();
        $this->assertEquals(StatusPixEnum::CONFIRMED, $pix->status);
    }

    public function test_can_process_pix_webhook_with_data_id(): void
    {
        $subacquirer = Subacquirer::factory()->create([
            'id' => 2,
            'name' => 'SubadqB',
        ]);
        $user = User::factory()->create(['subacquirer_id' => $subacquirer->id]);
        
        $pix = Pix::create([
            'user_id' => $user->id,
            'subacquirer_id' => $subacquirer->id,
            'external_id' => 'PX987654321',
            'amount' => 250.00,
            'status' => StatusPixEnum::PENDING,
        ]);

        $payload = [
            'type' => 'pix.status_update',
            'data' => [
                'id' => 'PX987654321',
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

        $response = $this->postJson('/api/webhook/pix', $payload);

        $response->assertStatus(204);
        
        $pix->refresh();
        $this->assertEquals(StatusPixEnum::PAID, $pix->status);
    }

    public function test_returns_400_when_payload_is_invalid(): void
    {
        $payload = [];

        $response = $this->postJson('/api/webhook/pix', $payload);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Invalid payload: transaction_id or data.id is required',
            ]);
    }

    public function test_returns_404_when_pix_not_found(): void
    {
        $payload = [
            'transaction_id' => 'non_existent_id',
        ];

        $response = $this->postJson('/api/webhook/pix', $payload);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'PIX not found',
            ]);
    }
}

