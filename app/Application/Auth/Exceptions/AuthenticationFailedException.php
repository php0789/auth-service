<?php

namespace App\Application\Auth\Exceptions;

use RuntimeException;

final class AuthenticationFailedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials.');
    }
}
