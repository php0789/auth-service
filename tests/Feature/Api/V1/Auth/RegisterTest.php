<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_register(): void
    {
        $response = $this->withHeader('X-Request-ID', 'registration-test')
            ->postJson('/api/v1/auth/register', [
                'email' => '  New.User@Example.COM ',
                'password' => 'StrongPass1',
                'password_confirmation' => 'StrongPass1',
            ]);

        $response
            ->assertCreated()
            ->assertHeader('X-Request-ID', 'registration-test')
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'new.user@example.com')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.email_verified_at', null)
            ->assertJsonPath('meta.request_id', 'registration-test')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.password_hash');

        $user = User::query()->sole();

        $this->assertTrue(Hash::check('StrongPass1', $user->password_hash));
        $this->assertDatabaseHas('outbox_events', [
            'event_type' => 'auth.user.registered.v1',
            'aggregate_id' => $user->uuid,
            'published_at' => null,
        ]);
    }

    public function test_registration_requires_a_valid_unique_email_and_confirmed_password(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'existing@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure([
                'error' => ['details' => ['email', 'password']],
                'meta' => ['request_id'],
            ]);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('outbox_events', 0);
    }
}
