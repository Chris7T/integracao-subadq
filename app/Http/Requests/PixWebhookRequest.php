<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PixWebhookRequest',
    description: 'Webhook payload format for PIX transactions. Supports two formats: SubadqA (flat structure) and SubadqB (nested data structure)',
    oneOf: [
        new OA\Schema(
            description: 'SubadqA PIX Webhook Format',
            required: ['event', 'transaction_id', 'pix_id', 'status', 'amount', 'payer_name', 'payer_cpf', 'payment_date', 'metadata'],
            properties: [
                new OA\Property(property: 'event', type: 'string', example: 'pix_payment_confirmed'),
                new OA\Property(property: 'transaction_id', type: 'string', example: 'f1a2b3c4d5e6'),
                new OA\Property(property: 'pix_id', type: 'string', example: 'PIX123456789'),
                new OA\Property(property: 'status', type: 'string', example: 'CONFIRMED'),
                new OA\Property(property: 'amount', type: 'number', example: 125.50),
                new OA\Property(property: 'payer_name', type: 'string', example: 'João da Silva'),
                new OA\Property(property: 'payer_cpf', type: 'string', example: '12345678900'),
                new OA\Property(property: 'payment_date', type: 'string', example: '2025-11-13T14:25:00Z'),
                new OA\Property(property: 'metadata', type: 'object'),
            ]
        ),
        new OA\Schema(
            description: 'SubadqB PIX Webhook Format',
            required: ['type', 'data', 'signature'],
            properties: [
                new OA\Property(property: 'type', type: 'string', example: 'pix.status_update'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    required: ['id', 'status', 'value', 'payer', 'confirmed_at'],
                    properties: [
                        new OA\Property(property: 'id', type: 'string', example: 'PX987654321'),
                        new OA\Property(property: 'status', type: 'string', example: 'PAID'),
                        new OA\Property(property: 'value', type: 'number', example: 250.00),
                        new OA\Property(
                            property: 'payer',
                            type: 'object',
                            required: ['name', 'document'],
                            properties: [
                                new OA\Property(property: 'name', type: 'string', example: 'Maria Oliveira'),
                                new OA\Property(property: 'document', type: 'string', example: '98765432100'),
                            ]
                        ),
                        new OA\Property(property: 'confirmed_at', type: 'string', example: '2025-11-13T14:40:00Z'),
                    ]
                ),
                new OA\Property(property: 'signature', type: 'string', example: 'd1c4b6f98eaa'),
            ]
        ),
    ]
)]
class PixWebhookRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'event' => ['required_without:type', 'string'],
            'transaction_id' => ['required_with:event', 'string'],
            'pix_id' => ['required_with:event', 'string'],
            'status' => ['required_with:event', 'string'],
            'amount' => ['required_with:event', 'numeric'],
            'payer_name' => ['required_with:event', 'string'],
            'payer_cpf' => ['required_with:event', 'string'],
            'payment_date' => ['required_with:event', 'string'],
            'metadata' => ['required_with:event', 'array'],
            'type' => ['required_without:event', 'string'],
            'data' => ['required_with:type', 'array'],
            'data.id' => ['required_with:type', 'string'],
            'data.status' => ['required_with:type', 'string'],
            'data.value' => ['required_with:type', 'numeric'],
            'data.payer' => ['required_with:type', 'array'],
            'data.payer.name' => ['required_with:type', 'string'],
            'data.payer.document' => ['required_with:type', 'string'],
            'data.confirmed_at' => ['required_with:type', 'string'],
            'signature' => ['required_with:type', 'string'],
        ];
    }
}

