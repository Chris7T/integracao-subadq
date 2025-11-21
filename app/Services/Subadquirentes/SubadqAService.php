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
        if (config('app.mock_subadquirer')) {
            return $this->mockPix();
        }

        $amountInCents = (int) ($data['amount'] * 100);

        $requestBody = [
            'merchant_id' => $data['user_id'],
            'amount' => $amountInCents,
            'currency' => 'BRL',
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
            Log::error('Error creating PIX in SubadqA', [
                'data' => $requestBody,
                'response' => $response->body(),
            ]);
            throw new \Exception('Error creating PIX in SubadqA');
        }

        return $response->json();
    }

    private function mockPix(): array
    {
        return [
            'transaction_id' => 'SP_SUBADQA_' . str_replace('-', '', Str::uuid()->toString()),
            'location' => 'https://subadqA.com/pix/loc/' . rand(100, 999),
            'qrcode' => '00020126530014BR.GOV.BCB.PIX0131backendtest@superpagamentos.com52040000530398654075000.005802BR5901N6001C6205050116304ACDA',
            'expires_at' => (string) now()->addHour()->timestamp,
            'status' => 'PENDING',
        ];
    }

    public function createWithdraw(array $data): array
    {
        if (config('app.mock_subadquirer')) {
            return $this->mockWithdraw();
        }

        $amountInCents = (int) ($data['amount'] * 100);

        $requestBody = [
            'merchant_id' => $data['user_id'],
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
            Log::error('Error creating withdraw in SubadqA', [
                'data' => $requestBody,
                'response' => $response->body(),
            ]);
            throw new \Exception('Error creating withdraw in SubadqA');
        }

        return $response->json();
    }

    private function mockWithdraw(): array
    {
        return [
            'transaction_id' => 'SP_SUBADQA_WD_' . str_replace('-', '', Str::uuid()->toString()),
            'withdraw_id' => 'WD_SUBADQA_' . str_replace('-', '', Str::uuid()->toString()),
            'status' => 'PENDING',
        ];
    }

    public function processPixWebhook(array $payload): Pix
    {
        $pix = Pix::where('transaction_id', $payload['transaction_id'])->firstOrFail();

        $pix->update([
            'pix_id' => $payload['pix_id'],
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
        $withdraw = Withdraw::where('transaction_id', $payload['transaction_id'])->firstOrFail();

        $withdraw->update([
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
            'transaction_id' => $pix->transaction_id,
            'pix_id' => Str::uuid()->toString(),
            'status' => 'PAID',
            'amount' => (float) $pix->amount,
            'payer_name' => $pix->payer_name,
            'payer_cpf' => $pix->payer_cpf,
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
            'withdraw_id' => $withdraw->withdraw_id,
            'transaction_id' => $withdraw->transaction_id,
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

