<?php

namespace App\Exceptions\Reconciliation;

use Exception;

class SessionNotInCorrectStatusException extends Exception
{
    public function __construct(
        string $currentStatus,
        string $expectedStatus,
        int $sessionId,
    ) {
        $message = sprintf(
            'Reconciliation session #%d is in status "%s" but expected status "%s".',
            $sessionId,
            $currentStatus,
            $expectedStatus,
        );

        parent::__construct($message);
    }
}
