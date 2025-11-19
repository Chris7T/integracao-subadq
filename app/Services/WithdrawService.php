<?php

namespace App\Services;

use App\Exceptions\UserNotFoundException;
use App\Exceptions\UserWithoutSubacquirerException;
use App\Jobs\CreateWithdrawJob;

class WithdrawService
{
    public function __construct(
        private UserService $userService
    ) {}

    public function create(array $data): void
    {
        $user = $this->userService->get($data['user_id']);

        if (!$user) {
            throw new UserNotFoundException();
        }

        if (!$user->subacquirer_id) {
            throw new UserWithoutSubacquirerException();
        }

        CreateWithdrawJob::dispatch(
            $user->id,
            $user->subacquirer_id,
            $data
        );
    }
}

