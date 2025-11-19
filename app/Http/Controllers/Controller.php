<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Subacquirer Integration API',
    description: 'API for integration with payment subacquirers, allowing asynchronous processing of PIX and withdrawals.',
    contact: new OA\Contact(
        name: 'API Support'
    )
)]
#[OA\Server(
    url: 'http://localhost:8080',
    description: 'Development server'
)]
#[OA\Tag(
    name: 'PIX',
    description: 'Endpoints for managing PIX transactions'
)]
#[OA\Tag(
    name: 'Withdraw',
    description: 'Endpoints for managing withdrawal requests'
)]
#[OA\Tag(
    name: 'Webhooks',
    description: 'Internal endpoints for receiving webhooks from subacquirers'
)]
abstract class Controller
{
    //
}
