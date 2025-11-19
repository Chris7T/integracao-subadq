<?php

namespace App\Exceptions;

use Exception;

class SubacquirerNotFoundException extends Exception
{
    public function __construct(int $id)
    {
        parent::__construct("Subacquirer with ID {$id} not found", 404);
    }
}

