<?php

namespace App\Http\Controllers;

use App\Exceptions\UserNotFoundException;
use App\Exceptions\UserWithoutSubacquirerException;
use App\Http\Requests\CreatePixRequest;
use App\Services\PixService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class PixController extends Controller
{
    public function __construct(
        private PixService $pixService
    ) {}

    #[OA\Post(
        path: '/api/pix',
        summary: 'Create PIX transaction',
        description: 'Creates a new PIX transaction asynchronously. The transaction will be processed in the background and the status will be updated via webhook.',
        tags: ['PIX'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CreatePixRequest')
        ),
        responses: [
            new OA\Response(response: 204, description: 'PIX transaction created successfully'),
            new OA\Response(
                response: 404,
                description: 'User not found',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string', example: 'User not found')])
            ),
            new OA\Response(
                response: 422,
                description: 'User without assigned subacquirer',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string', example: 'User does not have a subacquirer assigned')])
            ),
            new OA\Response(
                response: 500,
                description: 'Internal server error',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string', example: 'An unexpected error occurred while creating PIX')])
            ),
        ]
    )]
    public function store(CreatePixRequest $request): JsonResponse
    {
        try {
            $this->pixService->create($request->validated());

            return response()->json([], 204);
        } catch (UserNotFoundException|UserWithoutSubacquirerException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode());
        } catch (\Exception $e) {
            Log::error('Unexpected error creating PIX', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->validated(),
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred while creating PIX',
            ], 500);
        }
    }
}

