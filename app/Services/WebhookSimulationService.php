<?php

namespace App\Services;

class WebhookSimulationService
{
    public function getRandomStatus(?string $successStatus = null): ?string
    {
        $rand = rand(1, 100);
        
        if ($rand <= 70) {
            return null;
        } elseif ($rand <= 85) {
            return $successStatus;
        } elseif ($rand <= 95) {
            return 'FAILED';
        } else {
            return 'CANCELLED';
        }
    }
}

