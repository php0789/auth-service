<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Application\Auth\Actions\LoginAction;
use App\Application\Auth\DTOs\LoginData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;

final class LoginConroller extends Controller{

    public function __construct(
        private LoginAction $loginAction
    ){

    }

    public function __invoke(LoginRequest $request): JsonResponse{
        $data = new LoginData(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString()
        );

        $result = $this->loginAction->execute($data);

        return response()->json([
            'data' => [
                'user' => $result['user'],
                'token' => $result['token'],
                'token_type' => $result['token_type']
            ],
        ]);
    }
}