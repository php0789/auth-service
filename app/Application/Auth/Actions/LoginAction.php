<?php

namespace App\Application\Auth\Actions;

use App\Application\Auth\DTOs\LoginData;
use App\Domain\Auth\Contracts\TokenIssuerInterface;
use App\Models\User;
use App\Services\AuthenticationService;

final class LoginAction
{
    public function __construct(
        private AuthenticationService $authenticationService,
        private TokenIssuerInterface $tokenIssuer,
    ) {}

    /**
     * @return array{user: User, two_factor_required: true}|array{user: User, two_factor_required: false, token: string, token_type: string, expires_in: int}
     */
    public function execute(LoginData $data): array
    {
        $user = $this->authenticationService->authenticate(
            $data->email,
            $data->password
        );

        if ($user->isTwoFactorEnabled()) {
            return [
                'user' => $user,
                'two_factor_required' => true,
            ];
        }

        return [
            'user' => $user,
            'two_factor_required' => false,
            'token' => $this->tokenIssuer->issue($user),
            'token_type' => 'Bearer',
            'expires_in' => (int) config('auth_jwt.ttl', 900),
        ];
    }
}
