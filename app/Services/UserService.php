<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserService
{
    private const TTL = 300;

    public function get(int $userId): ?User
    {
        $userData = Cache::remember("user:{$userId}", self::TTL, function () use ($userId) {
            return User::find($userId)?->toArray();
        });

        if (!$userData) {
            return null;
        }

        return (new User())->forceFill($userData);
    }
}

