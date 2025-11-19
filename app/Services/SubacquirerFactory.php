<?php

namespace App\Services;

use App\Contracts\SubacquirerInterface;
use App\Enums\SubacquirerTypeEnum;
use App\Exceptions\SubacquirerNotFoundException;

class SubacquirerFactory
{
    public static function make(int $id): SubacquirerInterface
    {
        $type = SubacquirerTypeEnum::tryFrom($id);
        
        if (!$type) {
            throw new SubacquirerNotFoundException($id);
        }

        $serviceClass = $type->serviceClass();

        if (!class_exists($serviceClass)) {
            throw new SubacquirerNotFoundException($id);
        }

        return new $serviceClass($id);
    }
}

