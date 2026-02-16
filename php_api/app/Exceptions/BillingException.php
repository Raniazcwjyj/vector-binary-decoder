<?php

namespace App\Exceptions;

use RuntimeException;

class BillingException extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode = 'E_BILLING')
    {
        parent::__construct($message);
    }
}

