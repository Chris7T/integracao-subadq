<?php

namespace App\Exceptions;

use Exception;

class PixNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('PIX not found', 404);
    }
}

