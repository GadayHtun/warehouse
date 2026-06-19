<?php

namespace App\Http\Controllers;

use App\Exceptions\Reconciliation\LargeVarianceRequiresApprovalException;
use App\Models\Location;
use App\Models\Product;
use App\Models\ReconciliationSession;
use App\Models\ReconciliationCountLine;
use App\Services\ReconcilerEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReconciliationController extends Controller
{
    public function __construct(
        private ReconcilerEngine $reconciler,
    ) {}

    public function index()
    {
        $sessions = ReconciliationSession::query()
            ->with(['location', 'user'])
            ->whereNotIn('status', ['closed'])
            ->orderByDesc('updated_at')
            ->get();

        $closedSessions = ReconciliationSession::query()
            ->with(['location', 'user'])
            ->where('status', 'closed')
            ->orderByDesc('closed_at')
            ->limit(10)
            ->get();

        return view('reconciliation.index', compact('sessions', 'closedSessions'));
    }

    public function create()
    {
        $locations = Location::active()->orderBy('name')->get();
        $categories = Product::query()->distinct()->pluck('category');

        return view('reconciliation.create', compact('locations', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'category_filter' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $session = $this->reconciler->initiateSession(
            locationId: $validated['location_id'],
            user: Auth::user(),
            categoryFilter: $validated['category_filter'] ?? null,
            notes: $validated['notes'] ?? null,
        );

        return redirect()->route('reconciliation.count', $session);
    }

    public function count(ReconciliationSession $session)
    {
        abort_unless(in_array($session->status, ['draft', 'in_progress']), 404);

        $products = Product::query()
            ->when($session->category_filter, fn ($q) => $q->where('category', $session->category_filter))
            ->orderBy('name')
            ->get();

        $existingLines = $session->countLines()->with('product')->get()
            ->keyBy('product_id');

        return view('reconciliation.count', compact('session', 'products', 'existingLines'));
    }

    public function addCountLine(Request $request, ReconciliationSession $session)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'physical_quantity' => ['required', 'numeric', 'gte:0'],
        ]);

        $this->reconciler->addCountLine(
            session: $session,
            productId: $validated['product_id'],
            physicalQuantity: $validated['physical_quantity'],
        );

        return back()->with('success', 'Count line added.');
    }

    public function submit(ReconciliationSession $session)
    {
        $this->reconciler->submitSession($session);

        return redirect()->route('reconciliation.review', $session)
            ->with('success', 'Session submitted. Variances calculated.');
    }

    public function review(ReconciliationSession $session)
    {
        abort_unless(in_array($session->status, ['submitted', 'under_review']), 404);

        $summary = $this->reconciler->getSessionSummary($session);

        return view('reconciliation.review', compact('session', 'summary'));
    }

    public function resolveLine(Request $request, ReconciliationCountLine $line)
    {
        $validated = $request->validate([
            'resolution_type' => ['required', 'in:accept,recount,defer'],
            'resolution_note' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->reconciler->resolveLine(
                line: $line,
                resolutionType: $validated['resolution_type'],
                resolutionNote: $validated['resolution_note'],
                user: Auth::user(),
            );

            $message = match ($validated['resolution_type']) {
                'accept' => 'Line resolved. Adjustment created.',
                'recount' => 'Line flagged for recount.',
                'defer' => 'Line deferred for investigation.',
            };

            return back()->with('success', $message);
        } catch (LargeVarianceRequiresApprovalException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }

    public function approveLargeVariance(ReconciliationCountLine $line)
    {
        try {
            $this->reconciler->approveLargeVariance($line, Auth::user());
            return back()->with('success', 'Large variance approved. Adjustment created.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function rejectLargeVariance(Request $request, ReconciliationCountLine $line)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $this->reconciler->rejectLargeVariance($line, Auth::user(), $validated['reason']);

        return back()->with('success', 'Large variance rejected.');
    }

    public function finalize(ReconciliationSession $session)
    {
        try {
            $this->reconciler->finalizeSession($session);
            return redirect()->route('reconciliation.show', $session)
                ->with('success', 'Session finalized and closed.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(ReconciliationSession $session)
    {
        $summary = $this->reconciler->getSessionSummary($session);

        return view('reconciliation.show', compact('session', 'summary'));
    }
}
