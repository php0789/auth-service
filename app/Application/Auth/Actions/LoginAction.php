<?php

namespace App\Application\Auth\Actions;

use App\Application\Auth\CTOs\LoginData;
use App\Models\User;
use App\Services\Authentication\AuthenticationService;

final class LoginAction{
    public function __construct(
        private AuthenticationService $authenticationService,
    ){

    }

    public function execute(LoginData $data): array{
        $user = $this->authenticationService->authenticate(
            $data->email,
            $data->password
        );

        $token = $this->authenticationService->createToken($user);

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer'
        ];
    }
}