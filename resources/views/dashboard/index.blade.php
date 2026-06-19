@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Supervisor Dashboard</h1>
        <p class="text-gray-500 text-sm mt-1">Operational overview as of {{ now()->toDateTimeString() }}</p>
    </div>

    {{-- Top Metrics Row --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-sm text-gray-500">Pending Reconciliation</div>
            <div class="text-3xl font-bold text-gray-900">{{ $pending_reconciliation ?? 0 }}</div>
            <div class="text-xs text-gray-400 mt-1">Sessions awaiting review</div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-sm text-gray-500">Agent Findings</div>
            <div class="flex items-baseline gap-2 mt-1">
                @if(($agent_findings['critical'] ?? 0) > 0)
                    <span class="text-3xl font-bold text-red-600">{{ $agent_findings['critical'] }}</span>
                    <span class="text-xs text-red-500 font-medium">Critical</span>
                @endif
                <span class="text-3xl font-bold {{ ($agent_findings['critical'] ?? 0) > 0 ? 'text-gray-300' : 'text-gray-900' }}">{{ $agent_findings['total'] ?? 0 }}</span>
                <span class="text-xs text-gray-400">Total open</span>
            </div>
            <div class="flex gap-2 mt-1">
                <span class="text-xs px-1.5 py-0.5 rounded bg-red-100 text-red-700">{{ $agent_findings['critical'] ?? 0 }} crit</span>
                <span class="text-xs px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-700">{{ $agent_findings['warning'] ?? 0 }} warn</span>
                <span class="text-xs px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">{{ $agent_findings['info'] ?? 0 }} info</span>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-sm text-gray-500">Variance at a Glance (30d)</div>
            @php $v = $variance_at_a_glance ?? []; @endphp
            <div class="text-3xl font-bold {{ ($v['net_variance'] ?? 0) < 0 ? 'text-red-600' : 'text-gray-900' }}">
                {{ number_format(abs($v['net_variance'] ?? 0), 1) }}u
            </div>
            <div class="flex gap-2 mt-1 text-xs text-gray-400">
                <span>+{{ $v['positive_variance_count'] ?? 0 }} over</span>
                <span>-{{ $v['negative_variance_count'] ?? 0 }} under</span>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-sm text-gray-500">Low Stock Items</div>
            <div class="text-3xl font-bold {{ ($low_stock ?? 0) > 0 ? 'text-orange-600' : 'text-gray-900' }}">{{ $low_stock ?? 0 }}</div>
            <div class="text-xs text-gray-400 mt-1">Below reorder point</div>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-700">Recent Stock Movements</h2>
        </div>
        @if(!empty($recent_activity))
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-2">Product</th>
                        <th class="px-4 py-2">Direction</th>
                        <th class="px-4 py-2">Qty</th>
                        <th class="px-4 py-2">Location</th>
                        <th class="px-4 py-2">User</th>
                        <th class="px-4 py-2">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($recent_activity as $m)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium">{{ $m['product']['name'] ?? '-' }}</td>
                        <td class="px-4 py-2">
                            <span class="px-1.5 py-0.5 text-xs rounded {{ $m['direction'] === 'in' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $m['direction'] }}
                            </span>
                        </td>
                        <td class="px-4 py-2">{{ $m['quantity'] }}</td>
                        <td class="px-4 py-2">{{ $m['location']['name'] ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $m['user']['name'] ?? '-' }}</td>
                        <td class="px-4 py-2 text-gray-400">{{ \Carbon\Carbon::parse($m['created_at'])->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-4 text-sm text-gray-400">No recent stock movements.</div>
        @endif
    </div>
</div>
@endsection
