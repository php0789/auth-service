<?php

namespace App\Models;

use App\Enums\UserStatus;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUlids, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'uuid',
        'email',
        'password_hash',
        'status',
        'email_verified_at',
        'two_factor_enabled',
        'two_factor_secret',
        'locked_until',
        'password_changed_at',
    ];

    protected $hidden = [
        'password_hash',
        'two_factor_secret',
    ];

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = strtolower(trim($email));

        return $this;
    }

    public function getPasswordHash(): ?string
    {
        return $this->password_hash;
    }

    public function setPasswordHash(string $passwordHash): self
    {
        $this->password_hash = $passwordHash;

        return $this;
    }

    public function getStatus(): ?UserStatus
    {
        return $this->status;
    }

    public function setStatus(UserStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getEmailVerifiedAt(): ?CarbonImmutable
    {
        return $this->email_verified_at;
    }

    public function setEmailVerifiedAt(?DateTimeInterface $emailVerifiedAt): self
    {
        $this->email_verified_at = $emailVerifiedAt;

        return $this;
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled;
    }

    public function setTwoFactorEnabled(bool $twoFactorEnabled): self
    {
        $this->two_factor_enabled = $twoFactorEnabled;

        return $this;
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->two_factor_secret;
    }

    public function setTwoFactorSecret(?string $twoFactorSecret): self
    {
        $this->two_factor_secret = $twoFactorSecret;

        return $this;
    }

    public function getLockedUntil(): ?CarbonImmutable
    {
        return $this->locked_until;
    }

    public function setLockedUntil(?DateTimeInterface $lockedUntil): self
    {
        $this->locked_until = $lockedUntil;

        return $this;
    }

    public function getPasswordChangedAt(): ?CarbonImmutable
    {
        return $this->password_changed_at;
    }

    public function setPasswordChangedAt(?DateTimeInterface $passwordChangedAt): self
    {
        $this->password_changed_at = $passwordChangedAt;

        return $this;
    }

    public function getCreatedAt(): ?CarbonImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?CarbonImmutable
    {
        return $this->updated_at;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'locked_until' => 'immutable_datetime',
            'password_changed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'two_factor_enabled' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'status' => UserStatus::class,
        ];
    }
}
