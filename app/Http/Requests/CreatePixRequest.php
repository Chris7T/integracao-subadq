<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreatePixRequest',
    required: ['user_id', 'amount'],
    properties: [
        new OA\Property(property: 'user_id', type: 'integer', example: 1, description: 'User ID'),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 100.50, description: 'Transaction amount'),
        new OA\Property(property: 'payer_name', type: 'string', nullable: true, example: 'John Doe', description: 'Payer name'),
        new OA\Property(property: 'payer_cpf', type: 'string', nullable: true, example: '123.456.789-00', description: 'Payer CPF'),
        new OA\Property(property: 'metadata', type: 'object', nullable: true, example: ['key' => 'value'], description: 'Additional metadata'),
    ]
)]
class CreatePixRequest extends FormRequest
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
            'payer_name' => ['nullable', 'string', 'max:255'],
            'payer_cpf' => ['nullable', 'string', 'size:11', 'regex:/^[0-9]{11}$/'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

