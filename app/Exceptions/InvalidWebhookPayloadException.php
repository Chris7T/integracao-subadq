<?php

namespace App\Exceptions;

use Exception;

class InvalidWebhookPayloadException extends Exception
{
    public function __construct()
    {
        parent::__construct('Invalid payload: transaction_id or data.id is required', 400);
    }
}

