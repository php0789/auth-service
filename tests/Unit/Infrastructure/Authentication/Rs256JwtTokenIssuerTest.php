<?php

namespace Tests\Unit\Infrastructure\Authentication;

use App\Infrastructure\Authentication\Rs256JwtTokenIssuer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class Rs256JwtTokenIssuerTest extends TestCase
{
    private string $privateKeyPath;

    private string $publicKey;

    protected function setUp(): void
    {
        parent::setUp();

        $keyOptions = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $phpIniPath = php_ini_loaded_file();

        if (is_string($phpIniPath)) {
            $openSslConfigPath = dirname($phpIniPath).'/extras/ssl/openssl.cnf';

            if (is_file($openSslConfigPath)) {
                $keyOptions['config'] = $openSslConfigPath;
            }
        }

        $key = openssl_pkey_new($keyOptions);

        $this->assertNotFalse($key);
        $this->assertTrue(openssl_pkey_export($key, $privateKey, null, $keyOptions));

        $details = openssl_pkey_get_details($key);
        $this->assertNotFalse($details);

        $this->publicKey = $details['key'];
        $this->privateKeyPath = tempnam(sys_get_temp_dir(), 'jwt-key-');
        file_put_contents($this->privateKeyPath, $privateKey);
    }

    protected function tearDown(): void
    {
        Date::setTestNow();

        if (isset($this->privateKeyPath) && is_file($this->privateKeyPath)) {
            unlink($this->privateKeyPath);
        }

        parent::tearDown();
    }

    public function test_it_issues_a_short_lived_rs256_token_for_the_public_user_id(): void
    {
        $now = CarbonImmutable::now()->startOfSecond();
        Date::setTestNow($now);

        config()->set([
            'auth_jwt.private_key_path' => $this->privateKeyPath,
            'auth_jwt.issuer' => 'test-auth-service',
            'auth_jwt.audience' => 'test-user-service',
            'auth_jwt.ttl' => 600,
        ]);

        $uuid = (string) Str::ulid();
        $user = (new User)->setUuid($uuid);
        $token = (new Rs256JwtTokenIssuer)->issue($user);
        $claims = JWT::decode($token, new Key($this->publicKey, 'RS256'));

        $this->assertSame('test-auth-service', $claims->iss);
        $this->assertSame($uuid, $claims->sub);
        $this->assertSame('test-user-service', $claims->aud);
        $this->assertSame($now->getTimestamp(), $claims->iat);
        $this->assertSame($now->getTimestamp(), $claims->nbf);
        $this->assertSame($now->getTimestamp() + 600, $claims->exp);
        $this->assertTrue(Str::isUuid($claims->jti));
    }

    public function test_it_fails_when_the_signing_key_is_unavailable(): void
    {
        config()->set('auth_jwt.private_key_path', $this->privateKeyPath.'.missing');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JWT signing key unavailable.');

        (new Rs256JwtTokenIssuer)->issue(
            (new User)->setUuid((string) Str::ulid())
        );
    }

    public function test_it_requires_a_public_user_id(): void
    {
        config()->set('auth_jwt.private_key_path', $this->privateKeyPath);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JWT subject is unavailable.');

        (new Rs256JwtTokenIssuer)->issue(new User);
    }
}
