<?php

namespace App\Jobs;

use App\Models\Withdraw;
use App\Services\SubacquirerFactory;
use App\Services\WebhookSimulationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SimulateWithdrawWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Withdraw $withdraw
    ) {
        $this->delay(now()->addSeconds(5));
    }

    public function handle(WebhookSimulationService $webhookSimulationService): void
    {
        $service = SubacquirerFactory::make($this->withdraw->subacquirer_id);
        
        $randomStatus = $webhookSimulationService->getRandomStatus('SUCCESS');
        $payload = $service->generateSimulatedWithdrawWebhook($this->withdraw);
        
        if ($randomStatus) {
            if (isset($payload['status'])) {
                $payload['status'] = $randomStatus;
            } elseif (isset($payload['data']['status'])) {
                $payload['data']['status'] = $randomStatus;
            }
        }

        $webhookUrl = config('app.webhook_url') . '/api/webhook/withdraw';

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($webhookUrl, $payload);

            if (!$response->successful()) {
                Log::error('Webhook simulation failed', [
                    'withdraw_id' => $this->withdraw->id,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending webhook simulation', [
                'withdraw_id' => $this->withdraw->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

