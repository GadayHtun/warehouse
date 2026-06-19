<?php

namespace App\Exceptions\Reconciliation;

use Exception;

class LineAlreadyResolvedException extends Exception
{
    public function __construct(
        string $currentStatus,
        int $countLineId,
    ) {
        $message = sprintf(
            'Reconciliation count line #%d is already in status "%s" and cannot be resolved again.',
            $countLineId,
            $currentStatus,
        );

        parent::__construct($message);
    }
}
