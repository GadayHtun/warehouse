<?php

namespace App\Http\Controllers;

use App\Models\ReconciliationSession;
use App\Models\InventoryTransaction;
use App\Services\ReconcilerEngine;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function __construct(
        private ReconcilerEngine $reconciler,
    ) {}

    /**
     * Generate a reconciliation report as PDF for a specific session.
     */
    public function reconciliationReport(ReconciliationSession $session)
    {
        $summary = $this->reconciler->getSessionSummary($session);

        $pdf = Pdf::loadView('reports.reconciliation-session', [
            'session' => $session,
            'summary' => $summary,
        ]);

        return $pdf->download("reconciliation-session-{$session->id}.pdf");
    }

    /**
     * Export inventory transactions as CSV.
     */
    public function stockMovementLog()
    {
        $transactions = InventoryTransaction::query()
            ->with(['product:id,name,sku', 'location:id,name', 'user:id,name'])
            ->orderByDesc('created_at')
            ->limit(1000)
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="stock-movements.csv"',
        ];

        $callback = function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Type', 'Product SKU', 'Product', 'Location', 'Quantity', 'User', 'Date']);

            foreach ($transactions as $t) {
                fputcsv($handle, [
                    $t->id,
                    $t->type,
                    $t->product->sku ?? '-',
                    $t->product->name ?? '-',
                    $t->location->name ?? '-',
                    $t->quantity,
                    $t->user->name ?? '-',
                    $t->created_at->toDateTimeString(),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
