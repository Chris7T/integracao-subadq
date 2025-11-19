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

class SubadqBService implements SubacquirerInterface
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
            Log::error('Error creating PIX in SubadqB', [
                'data' => $data,
                'response' => $response->body(),
            ]);
            throw new \Exception('Error creating PIX in SubadqB');
        }

        return $response->json();
    }

    public function createWithdraw(array $data): array
    {
        $response = Http::withHeaders([
            'x-mock-response-name' => '[SUCESSO_WD] withdraw',
        ])->post("{$this->baseUrl}/withdraw", $data);

        if (!$response->successful()) {
            Log::error('Error creating withdraw in SubadqB', [
                'data' => $data,
                'response' => $response->body(),
            ]);
            throw new \Exception('Error creating withdraw in SubadqB');
        }

        return $response->json();
    }

    public function processPixWebhook(array $payload): Pix
    {
        $data = $payload['data'];
        $pix = Pix::where('external_id', $data['id'])->firstOrFail();

        $pix->update([
            'pix_id' => $data['id'] ?? $pix->pix_id,
            'status' => StatusPixEnum::fromSubacquirer($data['status'], 'subadq_b'),
            'amount' => $data['value'] ?? $pix->amount,
            'payer_name' => $data['payer']['name'] ?? $pix->payer_name,
            'payer_cpf' => $data['payer']['document'] ?? $pix->payer_cpf,
            'payment_date' => isset($data['confirmed_at']) ? $data['confirmed_at'] : $pix->payment_date,
            'metadata' => array_merge($pix->metadata ?? [], ['signature' => $payload['signature'] ?? null]),
        ]);

        return $pix->fresh();
    }

    public function processWithdrawWebhook(array $payload): Withdraw
    {
        $data = $payload['data'];
        $withdraw = Withdraw::where('external_id', $data['id'])->firstOrFail();

        $withdraw->update([
            'withdraw_id' => $data['id'] ?? $withdraw->withdraw_id,
            'status' => StatusWithdrawEnum::fromSubacquirer($data['status'], 'subadq_b'),
            'amount' => $data['amount'] ?? $withdraw->amount,
            'completed_at' => isset($data['processed_at']) ? $data['processed_at'] : $withdraw->completed_at,
            'bank_account' => $data['bank_account'] ?? $withdraw->bank_account,
            'metadata' => array_merge($withdraw->metadata ?? [], ['signature' => $payload['signature'] ?? null]),
        ]);

        return $withdraw->fresh();
    }

    public function generateSimulatedPixWebhook(Pix $pix): array
    {
        return [
            'type' => 'pix.status_update',
            'data' => [
                'id' => $pix->external_id,
                'status' => 'PAID',
                'value' => (float) $pix->amount,
                'payer' => [
                    'name' => 'Maria Oliveira',
                    'document' => '98765432100',
                ],
                'confirmed_at' => now()->toIso8601String(),
            ],
            'signature' => bin2hex(random_bytes(6)),
        ];
    }

    public function generateSimulatedWithdrawWebhook(Withdraw $withdraw): array
    {
        return [
            'type' => 'withdraw.status_update',
            'data' => [
                'id' => $withdraw->external_id,
                'status' => 'DONE',
                'amount' => (float) $withdraw->amount,
                'bank_account' => $withdraw->bank_account ?? [
                    'bank' => 'Nubank',
                    'agency' => '0001',
                    'account' => '1234567-8',
                ],
                'processed_at' => now()->toIso8601String(),
            ],
            'signature' => bin2hex(random_bytes(6)),
        ];
    }
}

