<?php

namespace App\Services;

use App\Application\Auth\Exceptions\AuthenticationFailedException;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class AuthenticationService
{
    public function authenticate(
        string $email,
        string $password
    ): User {
        $user = User::query()
            ->where('email', strtolower(trim($email)))
            ->first();

        if (
            ! $user ||
            ! Hash::check($password, $user->getPasswordHash())
        ) {
            throw new AuthenticationFailedException;
        }

        if (
            $user->getStatus() !== UserStatus::Active ||
            $user->getLockedUntil()?->isFuture()
        ) {
            throw new AuthenticationFailedException;
        }

        return $user;
    }
}
