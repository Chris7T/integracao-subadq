<?php

namespace App\Http\Controllers;

use App\Exceptions\UserNotFoundException;
use App\Exceptions\UserWithoutSubacquirerException;
use App\Http\Requests\CreateWithdrawRequest;
use App\Services\WithdrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class WithdrawController extends Controller
{
    public function __construct(
        private WithdrawService $withdrawService
    ) {}

    #[OA\Post(
        path: '/api/withdraw',
        summary: 'Create withdrawal request',
        description: 'Creates a new withdrawal request asynchronously. The transaction will be processed in the background and the status will be updated via webhook.',
        tags: ['Withdraw'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CreateWithdrawRequest')
        ),
        responses: [
            new OA\Response(response: 204, description: 'Withdrawal request created successfully'),
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
                content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string', example: 'An unexpected error occurred while creating withdraw')])
            ),
        ]
    )]
    public function store(CreateWithdrawRequest $request): JsonResponse
    {
        try {
            $this->withdrawService->create($request->validated());

            return response()->json([], 204);
        } catch (UserNotFoundException|UserWithoutSubacquirerException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode());
        } catch (\Exception $e) {
            Log::error('Unexpected error creating withdraw', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->validated(),
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred while creating withdraw',
            ], 500);
        }
    }
}

