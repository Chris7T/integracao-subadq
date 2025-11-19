<?php

namespace App\Services;

use App\Exceptions\InvalidWebhookPayloadException;
use App\Exceptions\PixNotFoundException;
use App\Exceptions\WithdrawNotFoundException;
use App\Models\Pix;
use App\Models\Withdraw;
use App\Services\SubacquirerFactory;

class WebhookService
{
    public function processPix(array $payload): Pix
    {
        if (!isset($payload['transaction_id']) && !isset($payload['data']['id'])) {
            throw new InvalidWebhookPayloadException();
        }

        $transactionId = $payload['transaction_id'] ?? $payload['data']['id'] ?? null;
        
        $pix = Pix::where('external_id', $transactionId)->first();

        if (!$pix) {
            throw new PixNotFoundException();
        }

        $subacquirerService = SubacquirerFactory::make($pix->subacquirer_id);
        
        return $subacquirerService->processPixWebhook($payload);
    }

    public function processWithdraw(array $payload): Withdraw
    {
        if (!isset($payload['transaction_id']) && !isset($payload['data']['id'])) {
            throw new InvalidWebhookPayloadException();
        }

        $transactionId = $payload['transaction_id'] ?? $payload['data']['id'] ?? null;
        
        $withdraw = Withdraw::where('external_id', $transactionId)->first();

        if (!$withdraw) {
            throw new WithdrawNotFoundException();
        }

        $subacquirerService = SubacquirerFactory::make($withdraw->subacquirer_id);
        
        return $subacquirerService->processWithdrawWebhook($payload);
    }
}

