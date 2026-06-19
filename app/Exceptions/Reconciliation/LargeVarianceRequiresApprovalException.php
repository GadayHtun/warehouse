<?php

namespace App\Exceptions\Reconciliation;

use Exception;

class LargeVarianceRequiresApprovalException extends Exception
{
    public function __construct(
        int $countLineId,
        float $variance,
        float $variancePercentage,
    ) {
        $message = sprintf(
            'Count line #%d has a large variance of %.2f units (%.2f%%) and requires supervisor approval before the adjustment can be applied.',
            $countLineId,
            $variance,
            $variancePercentage,
        );

        parent::__construct($message);
    }
}
