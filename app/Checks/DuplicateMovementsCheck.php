<?php

namespace App\Checks;

use App\Contracts\AgentCheck;
use App\Models\StockMovement;

/**
 * Detects stock movements with identical product, quantity, location,
 * and timestamp within a 60-second window.
 * Severity: critical. Frequency: daily.
 */
class DuplicateMovementsCheck implements AgentCheck
{
    private const DUPLICATE_WINDOW_SECONDS = 60;

    public function run(): array
    {
        // Find duplicates: same (product_id, location_id, quantity, direction)
        // within 60 seconds of each other
        $movements = StockMovement::query()
            ->with(['product', 'location', 'user'])
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('product_id')
            ->orderBy('created_at')
            ->get()
            ->groupBy(function ($m) {
                return implode(':', [$m->product_id, $m->location_id, $m->quantity, $m->direction]);
            });

        $findings = [];

        foreach ($movements as $group) {
            if ($group->count() < 2) {
                continue;
            }

            // Check for pairs within 60-second window
            $items = $group->values();
            for ($i = 0; $i < $items->count() - 1; $i++) {
                $diff = $items[$i]->created_at->diffInSeconds($items[$i + 1]->created_at);
                if ($diff <= self::DUPLICATE_WINDOW_SECONDS && $items[$i]->id !== $items[$i + 1]->id) {
                    $first = $items[$i];
                    // Check if they have different idempotency keys (not true duplicates but suspect)
                    if ($first->idempotency_key === $items[$i + 1]->idempotency_key) {
                        continue; // Same idempotency key — already handled
                    }

                    $findings[] = [
                        'check_type' => 'duplicate_movements',
                        'severity' => 'critical',
                        'product_id' => $first->product_id,
                        'location_id' => $first->location_id,
                        'title' => sprintf(
                            'Possible duplicate movement: %s %s at %s',
                            $first->direction === 'in' ? 'Stock-in' : 'Stock-out',
                            $first->product->name ?? 'Product #' . $first->product_id,
                            $first->location->code ?? 'Location #' . $first->location_id,
                        ),
                        'description' => sprintf(
                            'Two identical stock movements detected within %d seconds: '
                            . 'Movement #%d (by %s at %s) and Movement #%d (by %s at %s) — '
                            . 'both %s %.3f units of "%s" at %s. '
                            . 'Verify with the users whether both transactions are legitimate. '
                            . 'If duplicate, reverse one via an adjustment.',
                            $diff,
                            $first->id,
                            $first->user->name ?? 'Unknown',
                            $first->created_at->toDateTimeString(),
                            $items[$i + 1]->id,
                            $items[$i + 1]->user->name ?? 'Unknown',
                            $items[$i + 1]->created_at->toDateTimeString(),
                            $first->direction === 'in' ? 'received' : 'issued',
                            $first->quantity,
                            $first->product->name ?? 'Unknown',
                            $first->location->name ?? 'Unknown',
                        ),
                        'dedup_hash' => md5("duplicate:{$first->id}:{$items[$i+1]->id}"),
                    ];
                    break; // One finding per group
                }
            }
        }

        return $findings;
    }
}
