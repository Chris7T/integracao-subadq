<?php

namespace App\Services;

class WebhookSimulationService
{
    public function getRandomStatus(?string $successStatus = null): ?string
    {
        $rand = rand(1, 100);
        
        if ($rand <= 80) {
            return $successStatus;
        } elseif ($rand <= 90) {
            return 'FAILED';
        } else {
            return 'CANCELLED';
        }
    }
}

