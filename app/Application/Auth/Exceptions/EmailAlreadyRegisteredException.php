<?php

namespace App\Application\Auth\Exceptions;

use RuntimeException;

final class EmailAlreadyRegisteredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('An account with this email address already exists.');
    }
}
