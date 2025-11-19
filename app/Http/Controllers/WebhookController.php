<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidWebhookPayloadException;
use App\Exceptions\PixNotFoundException;
use App\Exceptions\WithdrawNotFoundException;
use App\Http\Requests\WebhookRequest;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class WebhookController extends Controller
{
    public function __construct(
        private WebhookService $webhookService
    ) {}

    #[OA\Post(
        path: '/api/webhook/pix',
        summary: 'Webhook for PIX transaction update',
        description: 'Internal endpoint to receive status update notifications for PIX transactions from subacquirers.',
        tags: ['Webhooks'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/WebhookRequest')
        ),
        responses: [
            new OA\Response(response: 204, description: 'Webhook processed successfully'),
            new OA\Response(
                response: 400,
                description: 'Invalid webhook payload',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string', example: 'Invalid webhook payload')])
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(property: 'errors', type: 'object', example: ['transaction_id' => ['The transaction_id must be a string.']])
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'PIX transaction not found',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string', example: 'PIX transaction not found')])
            ),
            new OA\Response(
                response: 500,
                description: 'Internal server error',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string', example: 'An unexpected error occurred while processing webhook')])
            ),
        ]
    )]
    public function pix(WebhookRequest $request): JsonResponse
    {
        try {
            $this->webhookService->processPix($request->validated());

            return response()->json([], 204);
        } catch (InvalidWebhookPayloadException|PixNotFoundException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode());
        } catch (\Exception $e) {
            Log::error('Unexpected error processing PIX webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->validated(),
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred while processing webhook',
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/webhook/withdraw',
        summary: 'Webhook for withdrawal request update',
        description: 'Internal endpoint to receive status update notifications for withdrawal requests from subacquirers.',
        tags: ['Webhooks'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/WebhookRequest')
        ),
        responses: [
            new OA\Response(response: 204, description: 'Webhook processed successfully'),
            new OA\Response(
                response: 400,
                description: 'Invalid webhook payload',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string', example: 'Invalid webhook payload')])
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(property: 'errors', type: 'object', example: ['transaction_id' => ['The transaction_id must be a string.']])
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Withdrawal request not found',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string', example: 'Withdraw transaction not found')])
            ),
            new OA\Response(
                response: 500,
                description: 'Internal server error',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string', example: 'An unexpected error occurred while processing webhook')])
            ),
        ]
    )]
    public function withdraw(WebhookRequest $request): JsonResponse
    {
        try {
            $this->webhookService->processWithdraw($request->validated());

            return response()->json([], 204);
        } catch (InvalidWebhookPayloadException|WithdrawNotFoundException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode());
        } catch (\Exception $e) {
            Log::error('Unexpected error processing withdraw webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->validated(),
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred while processing webhook',
            ], 500);
        }
    }
}

