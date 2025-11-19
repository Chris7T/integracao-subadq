<?php

namespace App\Enums;

enum SubacquirerTypeEnum: int
{
    case SUBADQ_A = 1;
    case SUBADQ_B = 2;

    public function label(): string
    {
        return match($this) {
            self::SUBADQ_A => 'SubadqA',
            self::SUBADQ_B => 'SubadqB',
        };
    }

    public function serviceClass(): string
    {
        return match($this) {
            self::SUBADQ_A => \App\Services\Subadquirentes\SubadqAService::class,
            self::SUBADQ_B => \App\Services\Subadquirentes\SubadqBService::class,
        };
    }
}

