<?php

namespace App\Exceptions\Reconciliation;

use Exception;

class ReasonTooShortException extends Exception
{
    public function __construct(int $providedLength, int $minimumLength = 10)
    {
        $message = sprintf(
            'Adjustment reason must be at least %d characters. Provided reason is %d characters.',
            $minimumLength,
            $providedLength,
        );

        parent::__construct($message);
    }
}
