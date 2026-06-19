<?php

namespace App\Http\Controllers;

use App\Models\AgentFinding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentFindingsController extends Controller
{
    public function index(Request $request)
    {
        $findings = AgentFinding::query()
            ->with(['product', 'location', 'reviewer'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->severity, fn ($q, $s) => $q->where('severity', $s))
            ->when($request->check_type, fn ($q, $t) => $q->where('check_type', $t))
            ->when($request->filled('status') === false, fn ($q) => $q->where('status', 'open'))
            ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
            ->orderByDesc('detected_at')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'open_critical' => AgentFinding::open()->bySeverity('critical')->count(),
            'open_warning' => AgentFinding::open()->bySeverity('warning')->count(),
            'open_info' => AgentFinding::open()->bySeverity('info')->count(),
            'acknowledged' => AgentFinding::where('status', 'acknowledged')->count(),
            'dismissed' => AgentFinding::where('status', 'dismissed')->count(),
        ];

        $checkTypes = AgentFinding::query()->distinct()->pluck('check_type');

        return view('agent-findings.index', compact('findings', 'counts', 'checkTypes'));
    }

    public function show(AgentFinding $finding)
    {
        $finding->load(['product', 'location', 'reviewer']);

        return view('agent-findings.show', compact('finding'));
    }

    public function acknowledge(AgentFinding $finding)
    {
        abort_unless($finding->status === 'open', 400, 'Finding is not open.');

        $finding->status = 'acknowledged';
        $finding->reviewer_id = Auth::id();
        $finding->reviewed_at = now();
        $finding->save();

        return back()->with('success', 'Finding acknowledged.');
    }

    public function dismiss(Request $request, AgentFinding $finding)
    {
        abort_unless($finding->status === 'open', 400, 'Finding is not open.');

        $validated = $request->validate([
            'review_note' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $finding->status = 'dismissed';
        $finding->reviewer_id = Auth::id();
        $finding->reviewed_at = now();
        $finding->review_note = $validated['review_note'];
        $finding->save();

        return back()->with('success', 'Finding dismissed with reason.');
    }
}
