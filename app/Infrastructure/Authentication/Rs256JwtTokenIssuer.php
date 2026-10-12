<?php

namespace App\Infrastructure\Authentication;

use App\Domain\Auth\Contracts\TokenIssuerInterface;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use RuntimeException;

final class Rs256JwtTokenIssuer implements TokenIssuerInterface
{
    public function issue(User $user): string
    {
        $privateKeyPath = config('auth_jwt.private_key_path');

        if (! is_string($privateKeyPath) || $privateKeyPath === '') {
            throw new RuntimeException('JWT signing key path is not configured.');
        }

        $privateKey = @file_get_contents($privateKeyPath);

        if ($privateKey === false) {
            throw new RuntimeException('JWT signing key unavailable.');
        }

        $subject = $user->getUuid();

        if ($subject === null) {
            throw new RuntimeException('JWT subject is unavailable.');
        }

        $now = Date::now()->getTimestamp();
        $ttl = (int) config('auth_jwt.ttl', 900);

        if ($ttl < 1) {
            throw new RuntimeException('JWT lifetime must be greater than zero.');
        }

        $payload = [
            'iss' => config('auth_jwt.issuer'),
            'sub' => $subject,
            'aud' => config('auth_jwt.audience'),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
            'jti' => (string) Str::uuid(),
        ];

        return JWT::encode(
            $payload,
            $privateKey,
            'RS256'
        );
    }
}
