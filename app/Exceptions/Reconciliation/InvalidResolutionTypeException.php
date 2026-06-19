<?php

namespace App\Exceptions\Reconciliation;

use Exception;

class InvalidResolutionTypeException extends Exception
{
    public function __construct(string $providedType)
    {
        $message = sprintf(
            'Invalid resolution type "%s". Must be one of: accept, recount, defer.',
            $providedType,
        );

        parent::__construct($message);
    }
}
