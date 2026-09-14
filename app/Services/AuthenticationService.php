<?php

namespace App\Services\Authentication;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class AuthenticationService
{
    public function authenticate(
        string $email,
        string $password
    ): User {
        $user = User::query()
            ->where('email', $email)
            ->first();

        if (
            ! $user ||
            ! Hash::check($password, $user->password_hash)
        ) {
            throw new \RuntimeException('Invalid credentials.');
        }

        return $user;
    }

    public function createToken(User $user): string
    {
        return $user
            ->createToken('auth-token')
            ->plainTextToken;
    }
}