<?php

namespace App\Exceptions;

use Exception;

class UserWithoutSubacquirerException extends Exception
{
    public function __construct()
    {
        parent::__construct('User does not have a subacquirer assigned', 422);
    }
}

