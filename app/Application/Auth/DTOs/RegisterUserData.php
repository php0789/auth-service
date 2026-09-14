<?php

namespace App\Application\Auth\DTOs;

final readonly class RegisterUserData
{
    public function __construct(
        public string $email,
        public string $password,
        public string $requestId,
    ) {}

    /**
     * @param  array{email: string, password: string}  $validated
     */
    public static function fromValidated(array $validated, string $requestId): self
    {
        return new self(
            email: $validated['email'],
            password: $validated['password'],
            requestId: $requestId,
        );
    }
}
