<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Application\Auth\Actions\RegisterUserAction;
use App\Application\Auth\DTOs\RegisterUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Resources\RegisteredUserResource;
use Illuminate\Http\JsonResponse;

final class RegisterController extends Controller
{
    public function __invoke(
        RegisterUserRequest $request,
        RegisterUserAction $action,
    ): JsonResponse {
        $requestId = (string) $request->attributes->get('request_id');
        $user = $action->execute(
            RegisterUserData::fromValidated($request->validated(), $requestId),
        );

        return response()->json([
            'success' => true,
            'data' => (new RegisteredUserResource($user))->resolve($request),
            'meta' => [
                'request_id' => $requestId,
            ],
        ], 201);
    }
}
