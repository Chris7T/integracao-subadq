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
use Illuminate\Support\Str;

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
        if (config('app.mock_subadquirer')) {
            return $this->mockPix();
        }

        $amountInCents = (int) ($data['amount'] * 100);

        $requestBody = [
            'seller_id' => $data['user_id'],
            'amount' => $amountInCents,
            'order_id' => $data['pix_id'],
            'payer' => [
                'name' => $data['payer_name'],
                'cpf_cnpj' => $data['payer_cpf'],
            ],
            'expires_in' => 3600,
        ];

        $response = Http::withHeaders([
            'x-mock-response-name' => '[SUCESSO_PIX] pix_create',
        ])->post("{$this->baseUrl}/pix/create", $requestBody);

        if (!$response->successful()) {
            Log::error('Error creating PIX in SubadqB', [
                'data' => $requestBody,
                'response' => $response->body(),
            ]);
            throw new \Exception('Error creating PIX in SubadqB');
        }

        return $response->json();
    }

    private function mockPix(): array
    {
        return [
            'transaction_id' => 'SP_ADQB_' . str_replace('-', '', Str::uuid()->toString()),
            'location' => 'https://subadqB.com/pix/loc/' . rand(100, 999),
            'qrcode' => '00020126530014BR.GOV.BCB.PIX0131backendtest@superpagamentos.com52040000530398654075000.005802BR5901N6001C6205050116304ACDA',
            'expires_at' => (string) now()->addHour()->timestamp,
            'status' => 'PROCESSING',
        ];
    }

    public function createWithdraw(array $data): array
    {
        if (config('app.mock_subadquirer')) {
            return $this->mockWithdraw();
        }

        $amountInCents = (int) ($data['amount'] * 100);

        $requestBody = [
            'seller_id' => $data['user_id'],
            'account' => [
                'bank_code' => $data['bank_account']['bank'],
                'agencia' => $data['bank_account']['agency'],
                'conta' => $data['bank_account']['account'],
                'type' => $data['bank_account']['type'] ?? 'checking',
            ],
            'amount' => $amountInCents,
        ];

        $response = Http::withHeaders([
            'x-mock-response-name' => 'SUCESSO_WD',
        ])->post("{$this->baseUrl}/withdraw", $requestBody);

        if (!$response->successful()) {
            Log::error('Error creating withdraw in SubadqB', [
                'data' => $requestBody,
                'response' => $response->body(),
            ]);
            throw new \Exception('Error creating withdraw in SubadqB');
        }

        return $response->json();
    }

    private function mockWithdraw(): array
    {
        return [
            'transaction_id' => 'SP_ADQB_WD_' . str_replace('-', '', Str::uuid()->toString()),
            'withdraw_id' => 'WD_SUBADQB_' . str_replace('-', '', Str::uuid()->toString()),
            'status' => 'PROCESSING',
        ];
    }

    public function processPixWebhook(array $payload): Pix
    {
        $data = $payload['data'];
        $pix = Pix::where('transaction_id', $payload['transaction_id'])->firstOrFail();

        $pix->update([
            'pix_id' => $data['id'],
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
        $withdraw = Withdraw::where('transaction_id', $payload['transaction_id'])->firstOrFail();

        $withdraw->update([
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
            'transaction_id' => $pix->transaction_id,
            'data' => [
                'id' => Str::uuid()->toString(),
                'status' => 'PAID',
                'value' => (float) $pix->amount,
                'payer' => [
                    'name' => $pix->payer_name,
                    'document' => $pix->payer_cpf,
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
            'transaction_id' => $withdraw->transaction_id,
            'data' => [
                'id' => Str::uuid()->toString(),
                'status' => 'DONE',
                'amount' => (float) $withdraw->amount,
                'bank_account' => $withdraw->bank_account,
                'processed_at' => now()->toIso8601String(),
            ],
            'signature' => bin2hex(random_bytes(6)),
        ];
    }
}
