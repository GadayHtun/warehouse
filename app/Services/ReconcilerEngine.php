<?php

namespace App\Services;

use App\Exceptions\Reconciliation\InvalidResolutionTypeException;
use App\Exceptions\Reconciliation\LargeVarianceRequiresApprovalException;
use App\Exceptions\Reconciliation\LineAlreadyResolvedException;
use App\Exceptions\Reconciliation\ReasonTooShortException;
use App\Exceptions\Reconciliation\SessionHasPendingLinesException;
use App\Exceptions\Reconciliation\SessionNotInCorrectStatusException;
use App\Exceptions\Reconciliation\UnauthorizedApprovalException;
use App\Models\Adjustment;
use App\Models\CurrentStock;
use App\Models\InventoryTransaction;
use App\Models\ReconciliationCountLine;
use App\Models\ReconciliationSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconcilerEngine
{
    /**
     * Large variance thresholds from the variance-analysis skill.
     * A variance is "large" if it exceeds 5% of system quantity OR exceeds 50 units.
     */
    private const LARGE_VARIANCE_PERCENTAGE_THRESHOLD = 5.0;
    private const LARGE_VARIANCE_UNITS_THRESHOLD = 50.0;

    private const VALID_STATUS_FOR_ADD_COUNT = ['draft', 'in_progress'];
    private const VALID_STATUS_FOR_SUBMIT = ['in_progress'];
    private const VALID_STATUS_FOR_RESOLVE = ['pending', 'flagged_recount'];
    private const VALID_RESOLUTION_TYPES = ['accept', 'recount', 'defer'];
    private const MINIMUM_REASON_LENGTH = 10;

    /**
     * Create a new reconciliation session in 'draft' status.
     */
    public function initiateSession(
        int $locationId,
        User $user,
        ?string $categoryFilter = null,
        ?string $notes = null,
    ): ReconciliationSession {
        $session = new ReconciliationSession();
        $session->location_id = $locationId;
        $session->user_id = $user->id;
        $session->status = 'draft';
        $session->category_filter = $categoryFilter;
        $session->notes = $notes;
        $session->started_at = now();
        $session->save();

        $this->auditLog('reconciliation_session_initiated', $session, [
            'user_id' => $user->id,
            'location_id' => $locationId,
            'category_filter' => $categoryFilter,
        ]);

        return $session;
    }

    /**
     * Add a physical count line to a reconciliation session.
     * Transitions session from 'draft' to 'in_progress' on first line.
     */
    public function addCountLine(
        ReconciliationSession $session,
        int $productId,
        float $physicalQuantity,
    ): ReconciliationCountLine {
        $this->assertSessionStatus($session, self::VALID_STATUS_FOR_ADD_COUNT);

        $line = new ReconciliationCountLine();
        $line->session_id = $session->id;
        $line->product_id = $productId;
        $line->physical_quantity = $physicalQuantity;
        $line->status = 'pending';
        $line->save();

        if ($session->status === 'draft') {
            $session->status = 'in_progress';
            $session->save();
        }

        $this->auditLog('count_line_added', $line, [
            'session_id' => $session->id,
            'product_id' => $productId,
            'physical_quantity' => $physicalQuantity,
        ]);

        return $line;
    }

    /**
     * Submit the reconciliation session: snapshot system quantities,
     * compute variances, and transition to 'submitted' status.
     */
    public function submitSession(ReconciliationSession $session): ReconciliationSession
    {
        $this->assertSessionStatus($session, self::VALID_STATUS_FOR_SUBMIT);

        $countLines = $session->countLines()->get();

        if ($countLines->isEmpty()) {
            throw new SessionNotInCorrectStatusException(
                $session->status,
                'in_progress (with at least one count line)',
                $session->id,
            );
        }

        DB::transaction(function () use ($session, $countLines) {
            foreach ($countLines as $line) {
                $currentStock = CurrentStock::query()
                    ->where('product_id', $line->product_id)
                    ->where('location_id', $session->location_id)
                    ->first();

                $systemQty = $currentStock?->quantity_on_hand ?? 0.0;

                $line->system_quantity_at_count = $systemQty;

                // Variance = physical - system
                $variance = $line->physical_quantity - $systemQty;
                $line->variance = $variance;

                // Variance percentage with edge-case handling
                if ($systemQty == 0 && $line->physical_quantity == 0) {
                    $line->variance_percentage = 0.0;
                } elseif ($systemQty == 0) {
                    $line->variance_percentage = 100.0;
                } else {
                    $line->variance_percentage = ($variance / $systemQty) * 100.0;
                }

                $line->save();
            }

            $session->status = 'submitted';
            $session->submitted_at = now();
            $session->save();
        });

        $this->auditLog('reconciliation_session_submitted', $session, [
            'session_id' => $session->id,
            'line_count' => $countLines->count(),
        ]);

        return $session->fresh();
    }

    /**
     * Resolve a single count line with a resolution type and note.
     *
     * 'accept'  — Creates adjustment (unless large variance needs approval).
     * 'recount' — Flag for recount, no adjustment.
     * 'defer'   — Defer for investigation, no adjustment.
     */
    public function resolveLine(
        ReconciliationCountLine $line,
        string $resolutionType,
        string $resolutionNote,
        User $user,
    ): ReconciliationCountLine {
        $this->assertLineStatus($line, self::VALID_STATUS_FOR_RESOLVE);
        $this->assertValidResolutionType($resolutionType);

        match ($resolutionType) {
            'accept' => $this->handleAcceptResolution($line, $resolutionNote, $user),
            'recount' => $this->handleRecountResolution($line, $resolutionNote),
            'defer' => $this->handleDeferResolution($line, $resolutionNote),
        };

        $session = $line->session;
        if ($session->status === 'submitted') {
            $session->status = 'under_review';
            $session->save();
        }

        $this->auditLog('count_line_resolved', $line, [
            'count_line_id' => $line->id,
            'resolution_type' => $resolutionType,
            'user_id' => $user->id,
        ]);

        return $line->fresh();
    }

    private function handleAcceptResolution(
        ReconciliationCountLine $line,
        string $resolutionNote,
        User $user,
    ): void {
        if (strlen($resolutionNote) < self::MINIMUM_REASON_LENGTH) {
            throw new ReasonTooShortException(strlen($resolutionNote), self::MINIMUM_REASON_LENGTH);
        }

        $line->status = 'resolved';
        $line->resolution_type = 'accept';
        $line->resolution_note = $resolutionNote;

        if ($this->isLargeVariance($line) && $line->large_variance_approval_status === 'not_required') {
            $line->large_variance_approval_status = 'pending_approval';
            $line->save();

            throw new LargeVarianceRequiresApprovalException(
                $line->id,
                $line->variance,
                $line->variance_percentage,
            );
        }

        if (!$this->isLargeVariance($line) || $line->large_variance_approval_status === 'approved') {
            $this->createAdjustment($line, $user);
        }

        $line->save();
    }

    private function handleRecountResolution(
        ReconciliationCountLine $line,
        string $resolutionNote,
    ): void {
        $line->status = 'flagged_recount';
        $line->resolution_type = 'recount';
        $line->resolution_note = $resolutionNote;
        $line->save();
    }

    private function handleDeferResolution(
        ReconciliationCountLine $line,
        string $resolutionNote,
    ): void {
        $line->status = 'deferred';
        $line->resolution_type = 'defer';
        $line->resolution_note = $resolutionNote;
        $line->save();
    }

    private function isLargeVariance(ReconciliationCountLine $line): bool
    {
        return abs($line->variance_percentage) > self::LARGE_VARIANCE_PERCENTAGE_THRESHOLD
            || abs($line->variance) > self::LARGE_VARIANCE_UNITS_THRESHOLD;
    }

    /**
     * Approve a large variance that is awaiting supervisor sign-off.
     * Approver must be supervisor and not the session submitter.
     */
    public function approveLargeVariance(
        ReconciliationCountLine $line,
        User $approver,
    ): Adjustment {
        $this->assertLargeVariancePendingApproval($line);
        $this->assertUserIsSupervisor($approver);

        if ($approver->id === $line->session->user_id) {
            throw new UnauthorizedApprovalException(
                'The approver cannot be the same user who submitted the reconciliation session.',
            );
        }

        $line->large_variance_approval_status = 'approved';
        $line->large_variance_approver_id = $approver->id;
        $line->save();

        $adjustment = $this->createAdjustment($line, $approver);

        $this->auditLog('large_variance_approved', $line, [
            'count_line_id' => $line->id,
            'approver_id' => $approver->id,
            'adjustment_id' => $adjustment->id,
        ]);

        return $adjustment;
    }

    /**
     * Reject a large variance. No adjustment is created.
     */
    public function rejectLargeVariance(
        ReconciliationCountLine $line,
        User $rejector,
        string $reason,
    ): void {
        $this->assertLargeVariancePendingApproval($line);

        $line->large_variance_approval_status = 'rejected';
        $line->large_variance_approver_id = $rejector->id;
        $existingNote = $line->resolution_note ?? '';
        $line->resolution_note = trim($existingNote . "\n[REJECTED by {$rejector->name}]: " . $reason);
        $line->save();

        $this->auditLog('large_variance_rejected', $line, [
            'count_line_id' => $line->id,
            'rejector_id' => $rejector->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Finalize and close a reconciliation session.
     */
    public function finalizeSession(ReconciliationSession $session): ReconciliationSession
    {
        $lines = $session->countLines()->get();

        $pendingLines = $lines->filter(fn ($l) => $l->status === 'pending');

        $unapprovedLarge = $lines->filter(
            fn ($l) => $l->large_variance_approval_status === 'pending_approval',
        );

        if ($pendingLines->isNotEmpty() || $unapprovedLarge->isNotEmpty()) {
            throw new SessionHasPendingLinesException(
                $session->id,
                $pendingLines->count(),
                $unapprovedLarge->count(),
            );
        }

        foreach ($lines as $line) {
            if (in_array($line->status, ['deferred', 'flagged_recount'], true)) {
                if (empty(trim($line->resolution_note ?? ''))) {
                    throw new SessionHasPendingLinesException($session->id, 1);
                }
            }
        }

        $session->status = 'closed';
        $session->closed_at = now();
        $session->save();

        $this->auditLog('reconciliation_session_closed', $session, [
            'session_id' => $session->id,
            'line_count' => $lines->count(),
        ]);

        return $session->fresh();
    }

    /**
     * Create an inventory adjustment and its corresponding inventory transaction.
     * Uses pessimistic locking on CurrentStock to prevent race conditions.
     */
    protected function createAdjustment(
        ReconciliationCountLine $line,
        User $user,
    ): Adjustment {
        return DB::transaction(function () use ($line, $user) {
            $session = $line->session;
            $productId = $line->product_id;
            $locationId = $session->location_id;

            $adjustmentQty = abs($line->variance);
            $transactionType = $line->variance > 0 ? 'adjustment_in' : 'adjustment_out';

            $idempotencyKey = sprintf(
                'recon_adj_%d_line_%d_v2',
                $session->id,
                $line->id,
            );

            // Pessimistic lock on the current_stock row
            $currentStock = CurrentStock::query()
                ->where('product_id', $productId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

            // Create the inventory transaction (append-only)
            $inventoryTransaction = new InventoryTransaction();
            $inventoryTransaction->product_id = $productId;
            $inventoryTransaction->location_id = $locationId;
            $inventoryTransaction->type = $transactionType;
            $inventoryTransaction->quantity = $adjustmentQty;
            $inventoryTransaction->reference_type = ReconciliationCountLine::class;
            $inventoryTransaction->reference_id = $line->id;
            $inventoryTransaction->user_id = $user->id;
            $inventoryTransaction->idempotency_key = $idempotencyKey;
            $inventoryTransaction->save();

            $adjustment = new Adjustment();
            $adjustment->count_line_id = $line->id;
            $adjustment->inventory_transaction_id = $inventoryTransaction->id;
            $adjustment->reason = $line->resolution_note;
            $adjustment->approved_by = $user->id;
            $adjustment->approved_at = now();
            $adjustment->save();

            if ($currentStock) {
                if ($transactionType === 'adjustment_in') {
                    $currentStock->quantity_on_hand += $adjustmentQty;
                } else {
                    $currentStock->quantity_on_hand -= $adjustmentQty;
                }
                $currentStock->save();
            } elseif ($transactionType === 'adjustment_in') {
                CurrentStock::create([
                    'product_id' => $productId,
                    'location_id' => $locationId,
                    'quantity_on_hand' => $adjustmentQty,
                ]);
            }

            $this->auditLog('adjustment_created', $adjustment, [
                'count_line_id' => $line->id,
                'transaction_type' => $transactionType,
                'quantity' => $adjustmentQty,
                'user_id' => $user->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            return $adjustment;
        });
    }

    /**
     * Return a structured summary of the reconciliation session.
     * Orders by largest absolute dollar variance first (investigation priority).
     */
    public function getSessionSummary(ReconciliationSession $session): array
    {
        $lines = $session->countLines()->with('product')->get();

        $totalLines = $lines->count();
        $linesPending = $lines->where('status', 'pending')->count();
        $linesResolved = $lines->where('status', 'resolved')->count();
        $linesDeferred = $lines->where('status', 'deferred')->count();
        $linesFlaggedRecount = $lines->where('status', 'flagged_recount')->count();
        $linesPendingApproval = $lines->where('large_variance_approval_status', 'pending_approval')->count();

        $totalVariance = $lines->sum('variance');
        $absoluteVariance = $lines->sum(fn ($l) => abs($l->variance));

        $netFinancialImpact = 0.0;
        $lineSummaries = [];

        foreach ($lines as $line) {
            $costPrice = $line->product->cost_price ?? 0.0;
            $dollarVariance = $line->variance * $costPrice;
            $netFinancialImpact += $dollarVariance;

            $lineSummaries[] = [
                'count_line_id' => $line->id,
                'product_id' => $line->product_id,
                'product_sku' => $line->product->sku ?? null,
                'product_name' => $line->product->name ?? null,
                'physical_quantity' => $line->physical_quantity,
                'system_quantity' => $line->system_quantity_at_count,
                'variance' => $line->variance,
                'variance_percentage' => $line->variance_percentage,
                'cost_price' => $costPrice,
                'dollar_variance' => $dollarVariance,
                'status' => $line->status,
                'resolution_type' => $line->resolution_type,
                'large_variance_approval_status' => $line->large_variance_approval_status,
            ];
        }

        // Sort by largest absolute dollar variance descending
        usort($lineSummaries, fn ($a, $b) => abs($b['dollar_variance']) <=> abs($a['dollar_variance']));

        $topVariances = array_slice($lineSummaries, 0, 10);
        $positiveVariances = array_values(array_filter($lineSummaries, fn ($l) => $l['variance'] > 0));
        $negativeVariances = array_values(array_filter($lineSummaries, fn ($l) => $l['variance'] < 0));
        $zeroVariances = array_values(array_filter($lineSummaries, fn ($l) => $l['variance'] == 0));

        $largeVariances = array_filter($lineSummaries, function ($l) {
            return ($l['system_quantity'] > 0 && abs($l['variance_percentage']) > self::LARGE_VARIANCE_PERCENTAGE_THRESHOLD)
                || abs($l['variance']) > self::LARGE_VARIANCE_UNITS_THRESHOLD;
        });

        return [
            'session_id' => $session->id,
            'location_id' => $session->location_id,
            'status' => $session->status,
            'started_at' => $session->started_at,
            'submitted_at' => $session->submitted_at,
            'closed_at' => $session->closed_at,
            'total_lines' => $totalLines,
            'lines_pending' => $linesPending,
            'lines_resolved' => $linesResolved,
            'lines_deferred' => $linesDeferred,
            'lines_flagged_recount' => $linesFlaggedRecount,
            'large_variances_pending_approval' => $linesPendingApproval,
            'total_variance_units' => $totalVariance,
            'absolute_variance_units' => $absoluteVariance,
            'net_financial_impact' => round($netFinancialImpact, 2),
            'positive_variances_count' => count($positiveVariances),
            'negative_variances_count' => count($negativeVariances),
            'zero_variance_lines' => count($zeroVariances),
            'large_variance_lines' => count($largeVariances),
            'investigation_priority' => $topVariances,
            'variances_by_direction' => [
                'positive' => $positiveVariances,
                'negative' => $negativeVariances,
                'zero' => $zeroVariances,
            ],
        ];
    }

    // ───────────────────────────────────
    // Assertion Helpers
    // ───────────────────────────────────

    private function assertSessionStatus(ReconciliationSession $session, array $expectedStatuses): void
    {
        if (!in_array($session->status, $expectedStatuses, true)) {
            throw new SessionNotInCorrectStatusException(
                $session->status,
                implode(' or ', $expectedStatuses),
                $session->id,
            );
        }
    }

    private function assertLineStatus(ReconciliationCountLine $line, array $expectedStatuses): void
    {
        if (!in_array($line->status, $expectedStatuses, true)) {
            throw new LineAlreadyResolvedException($line->status, $line->id);
        }
    }

    private function assertValidResolutionType(string $type): void
    {
        if (!in_array($type, self::VALID_RESOLUTION_TYPES, true)) {
            throw new InvalidResolutionTypeException($type);
        }
    }

    private function assertLargeVariancePendingApproval(ReconciliationCountLine $line): void
    {
        if ($line->large_variance_approval_status !== 'pending_approval') {
            throw new UnauthorizedApprovalException(
                sprintf(
                    'Count line #%d is not in pending_approval status (current: %s).',
                    $line->id,
                    $line->large_variance_approval_status ?? 'none',
                ),
            );
        }
    }

    private function assertUserIsSupervisor(User $user): void
    {
        if ($user->role !== 'supervisor') {
            throw new UnauthorizedApprovalException(
                sprintf(
                    'User #%d (role: %s) is not authorized to approve large variances. Required role: supervisor.',
                    $user->id,
                    $user->role ?? 'none',
                ),
            );
        }
    }

    /**
     * Write an entry to the audit log channel.
     */
    private function auditLog(string $action, object $subject, array $context = []): void
    {
        Log::channel('audit')->info($action, array_merge([
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id ?? null,
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }
}
