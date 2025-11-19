<?php

namespace App\Jobs;

use App\Enums\StatusPixEnum;
use App\Jobs\SimulatePixWebhookJob;
use App\Models\Pix;
use App\Services\SubacquirerFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreatePixJob implements ShouldQueue
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
            $pix = Pix::create([
                'user_id' => $this->userId,
                'subacquirer_id' => $this->subacquirerId,
                'external_id' => null,
                'pix_id' => null,
                'amount' => $this->data['amount'],
                'status' => StatusPixEnum::PENDING,
                'payer_name' => $this->data['payer_name'] ?? null,
                'payer_cpf' => $this->data['payer_cpf'] ?? null,
                'metadata' => $this->data['metadata'] ?? [],
            ]);

            $subacquirerService = SubacquirerFactory::make($this->subacquirerId);

            $response = $subacquirerService->createPix([
                'amount' => $this->data['amount'],
                'payer_name' => $this->data['payer_name'] ?? null,
                'payer_cpf' => $this->data['payer_cpf'] ?? null,
                'metadata' => $this->data['metadata'] ?? [],
            ]);

            $pix->update([
                'external_id' => $response['transaction_id'] ?? null,
                'pix_id' => $response['pix_id'] ?? null,
                'status' => StatusPixEnum::PROCESSING,
            ]);

            SimulatePixWebhookJob::dispatch($pix->fresh());
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('CreatePixJob failed permanently', [
            'user_id' => $this->userId,
            'subacquirer_id' => $this->subacquirerId,
            'amount' => $this->data['amount'],
            'error' => $exception->getMessage(),
            'exception_type' => get_class($exception),
        ]);

        Pix::create([
            'user_id' => $this->userId,
            'subacquirer_id' => $this->subacquirerId,
            'external_id' => null,
            'pix_id' => null,
            'amount' => $this->data['amount'],
            'status' => StatusPixEnum::FAILED,
            'payer_name' => $this->data['payer_name'] ?? null,
            'payer_cpf' => $this->data['payer_cpf'] ?? null,
            'metadata' => array_merge($this->data['metadata'] ?? [], [
                'error' => $exception->getMessage(),
                'failed_at' => now()->toIso8601String(),
            ]),
        ]);
    }
}

