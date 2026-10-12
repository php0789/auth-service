<?php

namespace App\Domain\Auth\Contracts;

use App\Models\User;

interface TokenIssuerInterface
{
    public function issue(User $user): string;
}