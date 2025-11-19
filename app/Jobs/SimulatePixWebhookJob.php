<?php

namespace App\Jobs;

use App\Models\Pix;
use App\Services\SubacquirerFactory;
use App\Services\WebhookSimulationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SimulatePixWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Pix $pix
    ) {
        $this->delay(now()->addSeconds(rand(5, 30)));
    }

    public function handle(WebhookSimulationService $webhookSimulationService): void
    {
        $service = SubacquirerFactory::make($this->pix->subacquirer_id);
        
        $randomStatus = $webhookSimulationService->getRandomStatus('PAID');
        $payload = $service->generateSimulatedPixWebhook($this->pix);
        
        if ($randomStatus) {
            if (isset($payload['status'])) {
                $payload['status'] = $randomStatus;
            } elseif (isset($payload['data']['status'])) {
                $payload['data']['status'] = $randomStatus;
            }
        }

        $webhookUrl = config('app.url') . '/api/webhook/pix';

        try {
            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if (!$response->successful()) {
                Log::error('Webhook simulation failed', [
                    'pix_id' => $this->pix->id,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending webhook simulation', [
                'pix_id' => $this->pix->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

