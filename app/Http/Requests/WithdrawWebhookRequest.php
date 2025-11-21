<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WithdrawWebhookRequest',
    description: 'Webhook payload format for withdrawal transactions. Supports two formats: SubadqA (flat structure) and SubadqB (nested data structure)',
    oneOf: [
        new OA\Schema(
            description: 'SubadqA Withdraw Webhook Format',
            required: ['event', 'withdraw_id', 'transaction_id', 'status', 'amount', 'requested_at', 'completed_at', 'metadata'],
            properties: [
                new OA\Property(property: 'event', type: 'string', example: 'withdraw_completed'),
                new OA\Property(property: 'withdraw_id', type: 'string', example: 'WD123456789'),
                new OA\Property(property: 'transaction_id', type: 'string', example: 'T987654321'),
                new OA\Property(property: 'status', type: 'string', example: 'SUCCESS'),
                new OA\Property(property: 'amount', type: 'number', example: 500.00),
                new OA\Property(property: 'requested_at', type: 'string', example: '2025-11-13T13:10:00Z'),
                new OA\Property(property: 'completed_at', type: 'string', example: '2025-11-13T13:12:30Z'),
                new OA\Property(property: 'metadata', type: 'object'),
            ]
        ),
        new OA\Schema(
            description: 'SubadqB Withdraw Webhook Format',
            required: ['type', 'data', 'signature'],
            properties: [
                new OA\Property(property: 'type', type: 'string', example: 'withdraw.status_update'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    required: ['id', 'status', 'amount', 'bank_account', 'processed_at'],
                    properties: [
                        new OA\Property(property: 'id', type: 'string', example: 'WDX54321'),
                        new OA\Property(property: 'status', type: 'string', example: 'DONE'),
                        new OA\Property(property: 'amount', type: 'number', example: 850.00),
                        new OA\Property(
                            property: 'bank_account',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'bank', type: 'string', example: 'Nubank'),
                                new OA\Property(property: 'agency', type: 'string', example: '0001'),
                                new OA\Property(property: 'account', type: 'string', example: '1234567-8'),
                            ]
                        ),
                        new OA\Property(property: 'processed_at', type: 'string', example: '2025-11-13T13:45:10Z'),
                    ]
                ),
                new OA\Property(property: 'signature', type: 'string', example: 'aabbccddeeff112233'),
            ]
        ),
    ]
)]
class WithdrawWebhookRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'event' => ['required_without:type', 'string'],
            'withdraw_id' => ['required_with:event', 'string'],
            'transaction_id' => ['required_with:event', 'string'],
            'status' => ['required_with:event', 'string'],
            'amount' => ['required_with:event', 'numeric'],
            'requested_at' => ['required_with:event', 'string'],
            'completed_at' => ['required_with:event', 'string'],
            'metadata' => ['required_with:event', 'array'],
            'type' => ['required_without:event', 'string'],
            'data' => ['required_with:type', 'array'],
            'data.id' => ['required_with:type', 'string'],
            'data.status' => ['required_with:type', 'string'],
            'data.amount' => ['required_with:type', 'numeric'],
            'data.bank_account' => ['required_with:type', 'array'],
            'data.processed_at' => ['required_with:type', 'string'],
            'signature' => ['required_with:type', 'string'],
        ];
    }
}

