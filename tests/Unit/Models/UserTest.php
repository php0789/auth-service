<?php

namespace Tests\Unit\Models;

use App\Enums\UserStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class UserTest extends TestCase
{
    public function test_getters_and_setters_match_the_user_schema(): void
    {
        $verifiedAt = CarbonImmutable::parse('2026-09-13 10:00:00');
        $lockedUntil = CarbonImmutable::parse('2026-09-13 11:00:00');
        $passwordChangedAt = CarbonImmutable::parse('2026-09-13 09:00:00');

        $user = (new User)
            ->setUuid('01K4USER000000000000000000')
            ->setEmail('  USER@Example.COM ')
            ->setPasswordHash('hashed-password')
            ->setStatus(UserStatus::Locked)
            ->setEmailVerifiedAt($verifiedAt)
            ->setTwoFactorEnabled(true)
            ->setTwoFactorSecret('two-factor-secret')
            ->setLockedUntil($lockedUntil)
            ->setPasswordChangedAt($passwordChangedAt);

        $this->assertNull($user->getId());
        $this->assertSame('01K4USER000000000000000000', $user->getUuid());
        $this->assertSame('user@example.com', $user->getEmail());
        $this->assertSame('hashed-password', $user->getPasswordHash());
        $this->assertSame(UserStatus::Locked, $user->getStatus());
        $this->assertTrue($verifiedAt->equalTo($user->getEmailVerifiedAt()));
        $this->assertTrue($user->isTwoFactorEnabled());
        $this->assertSame('two-factor-secret', $user->getTwoFactorSecret());
        $this->assertTrue($lockedUntil->equalTo($user->getLockedUntil()));
        $this->assertTrue($passwordChangedAt->equalTo($user->getPasswordChangedAt()));
        $this->assertNull($user->getCreatedAt());
        $this->assertNull($user->getUpdatedAt());

        $attributes = $user->getAttributes();
        $this->assertNotSame('two-factor-secret', $attributes['two_factor_secret']);
        $this->assertArrayNotHasKey('password_hash', $user->toArray());
        $this->assertArrayNotHasKey('two_factor_secret', $user->toArray());
    }
}
