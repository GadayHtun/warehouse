<?php

namespace App\Exceptions\Reconciliation;

use Exception;

class SessionHasPendingLinesException extends Exception
{
    public function __construct(
        int $sessionId,
        int $pendingCount,
        int $unapprovedLargeVarianceCount = 0,
    ) {
        $parts = [
            sprintf(
                'Reconciliation session #%d cannot be finalized: %d count line(s) are still pending resolution.',
                $sessionId,
                $pendingCount,
            ),
        ];

        if ($unapprovedLargeVarianceCount > 0) {
            $parts[] = sprintf(
                '%d large variance line(s) still require supervisor approval.',
                $unapprovedLargeVarianceCount,
            );
        }

        parent::__construct(implode(' ', $parts));
    }
}
