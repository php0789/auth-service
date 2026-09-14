<?php

namespace App\Application\Auth\Actions;

use App\Application\Auth\DTOs\RegisterUserData;
use App\Application\Auth\Exceptions\EmailAlreadyRegisteredException;
use App\Enums\UserStatus;
use App\Models\OutboxEvent;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class RegisterUserAction
{
    public function execute(RegisterUserData $data): User
    {
        try {
            return DB::transaction(function () use ($data): User {
                $user = (new User)
                    ->setUuid((string) Str::ulid())
                    ->setEmail($data->email)
                    ->setPasswordHash(Hash::make($data->password))
                    ->setStatus(UserStatus::Pending);

                $user->save();

                OutboxEvent::query()->create([
                    'event_type' => 'auth.user.registered.v1',
                    'aggregate_id' => $user->getUuid(),
                    'payload' => [
                        'user_id' => $user->getUuid(),
                        'email' => $user->getEmail(),
                    ],
                    'metadata' => [
                        'correlation_id' => $data->requestId,
                        'source' => 'auth-service',
                    ],
                    'occurred_at' => now(),
                ]);

                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            throw new EmailAlreadyRegisteredException;
        }
    }
}
