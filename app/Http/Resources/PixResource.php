<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PixResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'external_id', type: 'string', nullable: true, example: 'txn_123456'),
        new OA\Property(property: 'pix_id', type: 'string', nullable: true, example: 'pix_789012'),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 100.50),
        new OA\Property(property: 'status', type: 'string', example: 'pending'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00Z'),
    ]
)]
class PixResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'pix_id' => $this->pix_id,
            'amount' => $this->amount,
            'status' => $this->status->value,
            'created_at' => $this->created_at,
        ];
    }
}

