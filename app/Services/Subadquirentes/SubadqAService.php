<?php

namespace App\Services\Subadquirentes;

use App\Contracts\SubacquirerInterface;
use App\Enums\StatusPixEnum;
use App\Enums\StatusWithdrawEnum;
use App\Models\Pix;
use App\Models\Subacquirer;
use App\Models\Withdraw;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubadqAService implements SubacquirerInterface
{
    private string $baseUrl;

    public function __construct(int $subacquirerId)
    {
        $subacquirer = Subacquirer::findOrFail($subacquirerId);
        $this->baseUrl = $subacquirer->base_url;
    }

    public function createPix(array $data): array
    {
        $response = Http::withHeaders([
            'x-mock-response-name' => '[SUCESSO_PIX] pix_create',
        ])->post("{$this->baseUrl}/pix/create", $data);

        if (!$response->successful()) {
            Log::error('Error creating PIX in SubadqA', [
                'data' => $data,
                'response' => $response->body(),
            ]);
            throw new \Exception('Error creating PIX in SubadqA');
        }

        return $response->json();
    }

    public function createWithdraw(array $data): array
    {
        $response = Http::withHeaders([
            'x-mock-response-name' => '[SUCESSO_WD] withdraw',
        ])->post("{$this->baseUrl}/withdraw", $data);

        if (!$response->successful()) {
            Log::error('Error creating withdraw in SubadqA', [
                'data' => $data,
                'response' => $response->body(),
            ]);
            throw new \Exception('Error creating withdraw in SubadqA');
        }

        return $response->json();
    }

    public function processPixWebhook(array $payload): Pix
    {
        $pix = Pix::where('external_id', $payload['transaction_id'])->firstOrFail();

        $pix->update([
            'pix_id' => $payload['pix_id'] ?? $pix->pix_id,
            'status' => StatusPixEnum::fromSubacquirer($payload['status'], 'subadq_a'),
            'amount' => $payload['amount'] ?? $pix->amount,
            'payer_name' => $payload['payer_name'] ?? $pix->payer_name,
            'payer_cpf' => $payload['payer_cpf'] ?? $pix->payer_cpf,
            'payment_date' => isset($payload['payment_date']) ? $payload['payment_date'] : $pix->payment_date,
            'metadata' => $payload['metadata'] ?? $pix->metadata,
        ]);

        return $pix->fresh();
    }

    public function processWithdrawWebhook(array $payload): Withdraw
    {
        $withdraw = Withdraw::where('external_id', $payload['transaction_id'])->firstOrFail();

        $withdraw->update([
            'withdraw_id' => $payload['withdraw_id'] ?? $withdraw->withdraw_id,
            'status' => StatusWithdrawEnum::fromSubacquirer($payload['status'], 'subadq_a'),
            'amount' => $payload['amount'] ?? $withdraw->amount,
            'completed_at' => isset($payload['completed_at']) ? $payload['completed_at'] : $withdraw->completed_at,
            'metadata' => $payload['metadata'] ?? $withdraw->metadata,
        ]);

        return $withdraw->fresh();
    }

    public function generateSimulatedPixWebhook(Pix $pix): array
    {
        return [
            'event' => 'pix_payment_confirmed',
            'transaction_id' => $pix->external_id,
            'pix_id' => $pix->pix_id ?? 'PIX' . strtoupper(uniqid()),
            'status' => 'CONFIRMED',
            'amount' => (float) $pix->amount,
            'payer_name' => 'João da Silva',
            'payer_cpf' => '12345678900',
            'payment_date' => now()->toIso8601String(),
            'metadata' => [
                'source' => 'SubadqA',
                'environment' => 'sandbox',
            ],
        ];
    }

    public function generateSimulatedWithdrawWebhook(Withdraw $withdraw): array
    {
        return [
            'event' => 'withdraw_completed',
            'withdraw_id' => $withdraw->withdraw_id ?? 'WD' . strtoupper(uniqid()),
            'transaction_id' => $withdraw->external_id,
            'status' => 'SUCCESS',
            'amount' => (float) $withdraw->amount,
            'requested_at' => $withdraw->created_at->toIso8601String(),
            'completed_at' => now()->toIso8601String(),
            'metadata' => [
                'source' => 'SubadqA',
                'destination_bank' => 'Itaú',
            ],
        ];
    }
}

