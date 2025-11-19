<?php

namespace App\Exceptions;

use Exception;

class WithdrawNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('Withdraw not found', 404);
    }
}

