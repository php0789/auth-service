<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Application\Auth\Actions\LoginAction;
use App\Application\Auth\DTOs\LoginData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;

final class LoginConroller extends Controller
{
    public function __construct(
        private LoginAction $loginAction
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $requestId = (string) $request->attributes->get('request_id');
        $data = new LoginData(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString()
        );

        $result = $this->loginAction->execute($data);

        if ($result['two_factor_required']) {
            return response()->json([
                'success' => true,
                'data' => [
                    'two_factor_required' => true,
                ],
                'meta' => [
                    'request_id' => $requestId,
                ],
            ], 202);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $result['user'],
                'token' => $result['token'],
                'token_type' => $result['token_type'],
                'expires_in' => $result['expires_in'],
                'two_factor_required' => false,
            ],
            'meta' => [
                'request_id' => $requestId,
            ],
        ]);
    }
}
