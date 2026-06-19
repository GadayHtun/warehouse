<?php

namespace App\Exceptions\Reconciliation;

use Exception;

class UnauthorizedApprovalException extends Exception
{
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }
}
