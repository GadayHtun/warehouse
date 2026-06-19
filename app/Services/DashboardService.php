<?php

namespace App\Services;

use App\Models\AgentFinding;
use App\Models\ReconciliationSession;
use App\Models\ReconciliationCountLine;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Get all dashboard widget data for the Supervisor landing page.
     * Cached for 5 minutes, invalidated on stock movement or reconciliation events.
     */
    public function getDashboardData(): array
    {
        return [
            'pending_reconciliation' => $this->getPendingReconciliationCount(),
            'agent_findings' => $this->getAgentFindingsCounts(),
            'variance_at_a_glance' => $this->getVarianceAtGlance(),
            'recent_activity' => $this->getRecentActivity(),
            'low_stock' => $this->getLowStockCount(),
        ];
    }

    public function getPendingReconciliationCount(): int
    {
        return Cache::remember('dashboard:pending_recon', 300, fn () =>
            ReconciliationSession::whereIn('status', ['submitted', 'under_review'])->count()
        );
    }

    public function getAgentFindingsCounts(): array
    {
        // 1-minute cache for findings counts
        return Cache::remember('dashboard:findings', 60, fn () => [
            'critical' => AgentFinding::open()->bySeverity('critical')->count(),
            'warning' => AgentFinding::open()->bySeverity('warning')->count(),
            'info' => AgentFinding::open()->bySeverity('info')->count(),
            'total' => AgentFinding::open()->count(),
        ]);
    }

    public function getVarianceAtGlance(): array
    {
        return Cache::remember('dashboard:variance', 300, function () {
            $lines = ReconciliationCountLine::query()
                ->whereHas('session', fn ($q) =>
                    $q->whereIn('status', ['submitted', 'under_review', 'closed'])
                        ->where('submitted_at', '>=', now()->subDays(30))
                )
                ->get();

            return [
                'total_absolute_variance' => $lines->sum(fn ($l) => abs($l->variance ?? 0)),
                'net_variance' => $lines->sum('variance') ?? 0,
                'positive_variance_count' => $lines->where('variance', '>', 0)->count(),
                'negative_variance_count' => $lines->where('variance', '<', 0)->count(),
                'days_covered' => 30,
            ];
        });
    }

    public function getRecentActivity(): array
    {
        return StockMovement::query()
            ->with(['product:id,name,sku', 'location:id,name', 'user:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function getLowStockCount(): int
    {
        return Cache::remember('dashboard:low_stock', 300, fn () =>
            \App\Models\CurrentStock::query()
                ->whereHas('product', function ($q) {
                    $q->whereRaw('current_stock.quantity_on_hand < products.reorder_point');
                })
                ->count()
        );
    }

    /**
     * Invalidate all dashboard cache keys.
     */
    public static function invalidateCache(): void
    {
        Cache::forget('dashboard:pending_recon');
        Cache::forget('dashboard:findings');
        Cache::forget('dashboard:variance');
        Cache::forget('dashboard:low_stock');
    }
}
