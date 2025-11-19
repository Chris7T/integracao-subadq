<?php

namespace App\Contracts;

use App\Models\Pix;
use App\Models\Withdraw;

interface SubacquirerInterface
{
    public function createPix(array $data): array;

    public function createWithdraw(array $data): array;

    public function processPixWebhook(array $payload): Pix;

    public function processWithdrawWebhook(array $payload): Withdraw;

    public function generateSimulatedPixWebhook(Pix $pix): array;

    public function generateSimulatedWithdrawWebhook(Withdraw $withdraw): array;
}

