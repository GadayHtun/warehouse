<?php

use App\Checks\DormantStockCheck;
use App\Checks\DuplicateMovementsCheck;
use App\Checks\NegativeStockCheck;
use App\Checks\RapidDepletionCheck;
use App\Checks\ReconciliationStalenessCheck;
use App\Checks\UnbalancedTransfersCheck;
use App\Jobs\RunAgentCheck;
use Illuminate\Support\Facades\Schedule;

// ──────────────────────────────────────────
// Database Agent — Scheduled Checks (per spec section 4.6.1)
// ──────────────────────────────────────────

// Hourly: Negative stock detection (critical data integrity check)
Schedule::job(new RunAgentCheck(NegativeStockCheck::class))
    ->hourly()
    ->withoutOverlapping()
    ->description('Agent: Negative Stock Check');

// Daily: Dormant stock, rapid depletion, duplicate movements, unbalanced transfers
Schedule::job(new RunAgentCheck(DormantStockCheck::class))
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->description('Agent: Dormant Stock Check');

Schedule::job(new RunAgentCheck(RapidDepletionCheck::class))
    ->dailyAt('03:15')
    ->withoutOverlapping()
    ->description('Agent: Rapid Depletion Check');

Schedule::job(new RunAgentCheck(DuplicateMovementsCheck::class))
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->description('Agent: Duplicate Movements Check');

Schedule::job(new RunAgentCheck(UnbalancedTransfersCheck::class))
    ->dailyAt('03:45')
    ->withoutOverlapping()
    ->description('Agent: Unbalanced Transfers Check');

// Weekly: Reconciliation staleness
Schedule::job(new RunAgentCheck(ReconciliationStalenessCheck::class))
    ->weekly()
    ->mondays()
    ->at('04:00')
    ->withoutOverlapping()
    ->description('Agent: Reconciliation Staleness Check');

// NOTE: VarianceDriftCheck runs after reconciliation sessions close (triggered programmatically)
// NOTE: PriceAnomalyCheck runs on product price change events (triggered programmatically)
