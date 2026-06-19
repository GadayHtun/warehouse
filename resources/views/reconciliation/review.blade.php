@extends('layouts.app')

@section('title', 'Review — Session #' . $session->id)

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Variance Review</h1>
            <p class="text-sm text-gray-500">
                Session #{{ $session->id }} — {{ $session->location->name }} — Status: {{ str_replace('_', ' ', $session->status) }}
            </p>
        </div>
        <form method="POST" action="{{ route('reconciliation.finalize', $session) }}"
            onsubmit="return confirm('Finalize this session? All lines must be resolved.')">
            @csrf
            <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">Finalize Session</button>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
        <div class="bg-white rounded-lg border p-3 text-center">
            <div class="text-2xl font-bold">{{ $summary['total_lines'] }}</div>
            <div class="text-xs text-gray-500">Total Lines</div>
        </div>
        <div class="bg-white rounded-lg border p-3 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ $summary['lines_pending'] }}</div>
            <div class="text-xs text-gray-500">Pending</div>
        </div>
        <div class="bg-white rounded-lg border p-3 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $summary['lines_resolved'] }}</div>
            <div class="text-xs text-gray-500">Resolved</div>
        </div>
        <div class="bg-white rounded-lg border p-3 text-center">
            <div class="text-2xl font-bold {{ $summary['net_financial_impact'] < 0 ? 'text-red-600' : 'text-green-600' }}">
                ${{ number_format($summary['net_financial_impact'], 2) }}
            </div>
            <div class="text-xs text-gray-500">Net $ Impact</div>
        </div>
        <div class="bg-white rounded-lg border p-3 text-center">
            <div class="text-2xl font-bold">{{ $summary['large_variance_lines'] }}</div>
            <div class="text-xs text-gray-500">Large Variances</div>
        </div>
        <div class="bg-white rounded-lg border p-3 text-center">
            <div class="text-2xl font-bold text-orange-600">{{ $summary['large_variances_pending_approval'] }}</div>
            <div class="text-xs text-gray-500">Awaiting Approval</div>
        </div>
    </div>

    {{-- Investigation Priority Lines --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-700">Count Lines (by investigation priority — largest $ variance first)</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-2">Product</th>
                    <th class="px-4 py-2 text-right">Physical</th>
                    <th class="px-4 py-2 text-right">System</th>
                    <th class="px-4 py-2 text-right">Variance</th>
                    <th class="px-4 py-2 text-right">%</th>
                    <th class="px-4 py-2 text-right">$ Impact</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($summary['investigation_priority'] as $item)
                @php $line = \App\Models\ReconciliationCountLine::find($item['count_line_id']); @endphp
                <tr class="hover:bg-gray-50 {{ $item['status'] === 'pending' ? 'bg-yellow-50' : '' }}">
                    <td class="px-4 py-2 font-medium">
                        {{ $item['product_sku'] }}<br>
                        <span class="text-xs text-gray-400">{{ $item['product_name'] }}</span>
                    </td>
                    <td class="px-4 py-2 text-right font-mono">{{ $item['physical_quantity'] }}</td>
                    <td class="px-4 py-2 text-right font-mono">{{ $item['system_quantity'] }}</td>
                    <td class="px-4 py-2 text-right font-mono {{ $item['variance'] > 0 ? 'text-green-600' : ($item['variance'] < 0 ? 'text-red-600' : '') }}">
                        {{ $item['variance'] > 0 ? '+' : '' }}{{ $item['variance'] }}
                    </td>
                    <td class="px-4 py-2 text-right text-xs {{ abs($item['variance_percentage']) > 5 ? 'font-bold text-orange-600' : 'text-gray-500' }}">
                        {{ number_format($item['variance_percentage'], 1) }}%
                    </td>
                    <td class="px-4 py-2 text-right font-mono {{ $item['dollar_variance'] < 0 ? 'text-red-600' : '' }}">
                        ${{ number_format($item['dollar_variance'], 2) }}
                    </td>
                    <td class="px-4 py-2">
                        <span class="px-1.5 py-0.5 text-xs rounded
                            @switch($item['status'])
                                @case('pending') bg-yellow-100 text-yellow-700 @break
                                @case('resolved') bg-green-100 text-green-700 @break
                                @case('deferred') bg-gray-100 text-gray-600 @break
                                @case('flagged_recount') bg-purple-100 text-purple-700 @break
                            @endswitch">
                            {{ str_replace('_', ' ', $item['status']) }}
                        </span>
                        @if($item['large_variance_approval_status'] === 'pending_approval')
                            <span class="ml-1 px-1.5 py-0.5 text-xs rounded bg-orange-100 text-orange-700">Needs Approval</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        @if(in_array($item['status'], ['pending', 'flagged_recount']) && $line)
                            <div class="flex flex-col gap-1">
                                @if($line->large_variance_approval_status === 'pending_approval')
                                    <form method="POST" action="{{ route('reconciliation.approve-large-variance', $line) }}" class="inline">
                                        @csrf
                                        <button class="text-xs text-green-600 hover:underline">Approve</button>
                                    </form>
                                    <button onclick="document.getElementById('reject-reason-{{ $line->id }}').classList.toggle('hidden')" class="text-xs text-red-600 hover:underline text-left">Reject</button>
                                    <form id="reject-reason-{{ $line->id }}" method="POST" action="{{ route('reconciliation.reject-large-variance', $line) }}" class="hidden">
                                        @csrf
                                        <input type="text" name="reason" placeholder="Rejection reason (min 10 chars)" minlength="10" required
                                            class="w-full text-xs px-1 py-0.5 border rounded mb-1">
                                        <button class="text-xs text-red-600 hover:underline">Confirm Reject</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('reconciliation.resolve-line', $line) }}" class="flex flex-col gap-1">
                                        @csrf
                                        <select name="resolution_type" class="text-xs border rounded px-1 py-0.5">
                                            <option value="accept">Accept</option>
                                            <option value="recount">Flag Recount</option>
                                            <option value="defer">Defer</option>
                                        </select>
                                        <input type="text" name="resolution_note" placeholder="Reason (min 10 chars)" minlength="10" required
                                            class="text-xs px-1 py-0.5 border rounded">
                                        <button class="text-xs text-brand-500 hover:underline">Resolve</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No count lines.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
