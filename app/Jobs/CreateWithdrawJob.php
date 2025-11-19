<?php

namespace App\Jobs;

use App\Enums\StatusWithdrawEnum;
use App\Jobs\SimulateWithdrawWebhookJob;
use App\Models\Withdraw;
use App\Services\SubacquirerFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateWithdrawJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];
    public $timeout = 120;

    public function __construct(
        public int $userId,
        public int $subacquirerId,
        public array $data
    ) {}

    public function handle(): void
    {
        DB::transaction(function () {
            $withdraw = Withdraw::create([
                'user_id' => $this->userId,
                'subacquirer_id' => $this->subacquirerId,
                'external_id' => null,
                'withdraw_id' => null,
                'transaction_id' => null,
                'amount' => $this->data['amount'],
                'status' => StatusWithdrawEnum::PENDING,
                'bank_account' => $this->data['bank_account'],
                'metadata' => $this->data['metadata'] ?? [],
            ]);

            $subacquirerService = SubacquirerFactory::make($this->subacquirerId);

            $response = $subacquirerService->createWithdraw([
                'amount' => $this->data['amount'],
                'bank_account' => $this->data['bank_account'],
                'metadata' => $this->data['metadata'] ?? [],
            ]);

            $withdraw->update([
                'external_id' => $response['transaction_id'] ?? null,
                'withdraw_id' => $response['withdraw_id'] ?? null,
                'transaction_id' => $response['transaction_id'] ?? null,
                'status' => StatusWithdrawEnum::PROCESSING,
            ]);

            SimulateWithdrawWebhookJob::dispatch($withdraw->fresh());
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('CreateWithdrawJob failed permanently', [
            'user_id' => $this->userId,
            'subacquirer_id' => $this->subacquirerId,
            'amount' => $this->data['amount'],
            'error' => $exception->getMessage(),
            'exception_type' => get_class($exception),
        ]);

        Withdraw::create([
            'user_id' => $this->userId,
            'subacquirer_id' => $this->subacquirerId,
            'external_id' => null,
            'withdraw_id' => null,
            'transaction_id' => null,
            'amount' => $this->data['amount'],
            'status' => StatusWithdrawEnum::FAILED,
            'bank_account' => $this->data['bank_account'],
            'metadata' => array_merge($this->data['metadata'] ?? [], [
                'error' => $exception->getMessage(),
                'failed_at' => now()->toIso8601String(),
            ]),
        ]);
    }
}

