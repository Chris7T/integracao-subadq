<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreateWithdrawRequest',
    required: ['user_id', 'amount', 'bank_account'],
    properties: [
        new OA\Property(property: 'user_id', type: 'integer', example: 1, description: 'User ID'),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 500.00, description: 'Withdrawal amount'),
        new OA\Property(
            property: 'bank_account',
            type: 'object',
            required: ['bank', 'agency', 'account'],
            properties: [
                new OA\Property(property: 'bank', type: 'string', example: '001', description: 'Bank code'),
                new OA\Property(property: 'agency', type: 'string', example: '1234', description: 'Bank agency'),
                new OA\Property(property: 'account', type: 'string', example: '56789-0', description: 'Account number'),
            ]
        ),
    ]
)]
class CreateWithdrawRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'bank_account' => ['required', 'array'],
            'bank_account.bank' => ['required', 'string'],
            'bank_account.agency' => ['required', 'string'],
            'bank_account.account' => ['required', 'string'],
        ];
    }
}

