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
        $pix = Pix::where('transaction_id', $payload['transaction_id'])->first();

        if (!$pix) {
            throw new PixNotFoundException();
        }

        $subacquirerService = SubacquirerFactory::make($pix->subacquirer_id);
        
        return $subacquirerService->processPixWebhook($payload);
    }

    public function processWithdraw(array $payload): Withdraw
    {
        $withdraw = Withdraw::where('transaction_id', $payload['transaction_id'])->first();

        if (!$withdraw) {
            throw new WithdrawNotFoundException();
        }

        $subacquirerService = SubacquirerFactory::make($withdraw->subacquirer_id);
        
        return $subacquirerService->processWithdrawWebhook($payload);
    }
}

