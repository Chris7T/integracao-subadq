<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WebhookRequest',
    properties: [
        new OA\Property(property: 'transaction_id', type: 'string', nullable: true, example: 'txn_123456', description: 'Transaction ID from subacquirer'),
        new OA\Property(
            property: 'data',
            type: 'object',
            nullable: true,
            properties: [
                new OA\Property(property: 'id', type: 'string', nullable: true, example: 'pix_789012', description: 'Alternative transaction ID'),
            ],
            description: 'Alternative webhook data'
        ),
    ]
)]
class WebhookRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'transaction_id' => ['nullable', 'string'],
            'data' => ['nullable', 'array'],
            'data.id' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric'],
            'pix_id' => ['nullable', 'string'],
            'withdraw_id' => ['nullable', 'string'],
            'payer_name' => ['nullable', 'string'],
            'payer_cpf' => ['nullable', 'string'],
            'payment_date' => ['nullable', 'string'],
            'completed_at' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'data.status' => ['nullable', 'string'],
            'data.value' => ['nullable', 'numeric'],
            'data.amount' => ['nullable', 'numeric'],
            'data.payer' => ['nullable', 'array'],
            'data.payer.name' => ['nullable', 'string'],
            'data.payer.document' => ['nullable', 'string'],
            'data.confirmed_at' => ['nullable', 'string'],
            'data.processed_at' => ['nullable', 'string'],
            'data.bank_account' => ['nullable', 'array'],
            'signature' => ['nullable', 'string'],
        ];
    }
}

