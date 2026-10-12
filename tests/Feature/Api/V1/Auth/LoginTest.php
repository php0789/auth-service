<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Domain\Auth\Contracts\TokenIssuerInterface;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    private TokenIssuerInterface $tokenIssuer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenIssuer = new class implements TokenIssuerInterface
        {
            public int $issuedTokens = 0;

            public function issue(User $user): string
            {
                $this->issuedTokens++;

                return 'signed-jwt';
            }
        };

        $this->app->instance(TokenIssuerInterface::class, $this->tokenIssuer);
    }

    public function test_an_active_user_without_two_factor_authentication_receives_a_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->withHeader('X-Request-ID', 'login-test')
            ->postJson('/api/v1/auth/login', [
                'email' => ' USER@example.com ',
                'password' => 'password',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.uuid', $user->uuid)
            ->assertJsonPath('data.token', 'signed-jwt')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.expires_in', 900)
            ->assertJsonPath('data.two_factor_required', false)
            ->assertJsonPath('meta.request_id', 'login-test')
            ->assertJsonMissingPath('data.user.password_hash')
            ->assertJsonMissingPath('data.user.two_factor_secret');

        $this->assertSame(1, $this->tokenIssuer->issuedTokens);
    }

    public function test_two_factor_authentication_must_be_completed_before_a_token_is_issued(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'two_factor_enabled' => true,
            'two_factor_secret' => 'encrypted-by-model-cast',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertAccepted()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.two_factor_required', true)
            ->assertJsonMissingPath('data.token')
            ->assertJsonMissingPath('data.user');

        $this->assertSame(0, $this->tokenIssuer->issuedTokens);
    }

    public function test_inactive_accounts_receive_the_same_generic_failure(): void
    {
        foreach ([UserStatus::Pending, UserStatus::Locked, UserStatus::Suspended] as $status) {
            User::factory()->create([
                'email' => $status->value.'@example.com',
                'status' => $status,
            ]);

            $this->postJson('/api/v1/auth/login', [
                'email' => $status->value.'@example.com',
                'password' => 'password',
            ])
                ->assertUnauthorized()
                ->assertJsonPath('error.code', 'INVALID_CREDENTIALS')
                ->assertJsonPath('error.message', 'Invalid credentials.')
                ->assertJsonMissingPath('data.token');
        }

        $this->assertSame(0, $this->tokenIssuer->issuedTokens);
    }

    public function test_a_temporarily_locked_active_account_cannot_receive_a_token(): void
    {
        User::factory()->create([
            'email' => 'locked@example.com',
            'locked_until' => now()->addMinute(),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'locked@example.com',
            'password' => 'password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');

        $this->assertSame(0, $this->tokenIssuer->issuedTokens);
    }

    public function test_an_expired_temporary_lock_does_not_block_an_active_account(): void
    {
        User::factory()->create([
            'email' => 'unlocked@example.com',
            'locked_until' => now()->subMinute(),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'unlocked@example.com',
            'password' => 'password',
        ])->assertOk();

        $this->assertSame(1, $this->tokenIssuer->issuedTokens);
    }

    public function test_unknown_users_and_wrong_passwords_receive_the_same_generic_failure(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        foreach ([
            ['email' => 'missing@example.com', 'password' => 'password'],
            ['email' => 'user@example.com', 'password' => 'wrong-password'],
        ] as $credentials) {
            $this->postJson('/api/v1/auth/login', $credentials)
                ->assertUnauthorized()
                ->assertJsonPath('error.code', 'INVALID_CREDENTIALS')
                ->assertJsonPath('error.message', 'Invalid credentials.');
        }

        $this->assertSame(0, $this->tokenIssuer->issuedTokens);
    }
}
