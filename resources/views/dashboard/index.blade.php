@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Warehouse Reconciliation Dashboard</h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Operational overview as of {{ now()->toDateTimeString() }}</p>
    </div>

    {{-- Top Metrics Row --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 transition-colors">
            <div class="text-sm text-gray-500 dark:text-gray-400">Pending Reconciliation</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $pending_reconciliation ?? 0 }}</div>
            <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">Sessions awaiting review</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 transition-colors">
            <div class="text-sm text-gray-500 dark:text-gray-400">Agent Findings</div>
            <div class="flex items-baseline gap-2 mt-1">
                @if(($agent_findings['critical'] ?? 0) > 0)
                    <span class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $agent_findings['critical'] }}</span>
                    <span class="text-xs text-red-500 dark:text-red-400 font-medium">Critical</span>
                @endif
                <span class="text-3xl font-bold {{ ($agent_findings['critical'] ?? 0) > 0 ? 'text-gray-300 dark:text-gray-600' : 'text-gray-900 dark:text-white' }}">{{ $agent_findings['total'] ?? 0 }}</span>
                <span class="text-xs text-gray-400 dark:text-gray-500">Total open</span>
            </div>
            <div class="flex gap-2 mt-1">
                <span class="text-xs px-1.5 py-0.5 rounded bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400">{{ $agent_findings['critical'] ?? 0 }} crit</span>
                <span class="text-xs px-1.5 py-0.5 rounded bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400">{{ $agent_findings['warning'] ?? 0 }} warn</span>
                <span class="text-xs px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400">{{ $agent_findings['info'] ?? 0 }} info</span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 transition-colors">
            <div class="text-sm text-gray-500 dark:text-gray-400">Variance at a Glance (30d)</div>
            @php $v = $variance_at_a_glance ?? []; @endphp
            <div class="text-3xl font-bold {{ ($v['net_variance'] ?? 0) < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                {{ number_format(abs($v['net_variance'] ?? 0), 1) }}u
            </div>
            <div class="flex gap-2 mt-1 text-xs text-gray-400 dark:text-gray-500">
                <span>+{{ $v['positive_variance_count'] ?? 0 }} over</span>
                <span>-{{ $v['negative_variance_count'] ?? 0 }} under</span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 transition-colors">
            <div class="text-sm text-gray-500 dark:text-gray-400">Low Stock Items</div>
            <div class="text-3xl font-bold {{ ($low_stock ?? 0) > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-900 dark:text-white' }}">{{ $low_stock ?? 0 }}</div>
            <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">Below reorder point</div>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden transition-colors">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Recent Stock Movements</h2>
        </div>
        @if(!empty($recent_activity))
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-xs text-gray-500 dark:text-gray-400 uppercase">
                    <tr>
                        <th class="px-4 py-2">Product</th>
                        <th class="px-4 py-2">Direction</th>
                        <th class="px-4 py-2">Qty</th>
                        <th class="px-4 py-2">Location</th>
                        <th class="px-4 py-2">User</th>
                        <th class="px-4 py-2">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($recent_activity as $m)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">{{ $m['product']['name'] ?? '-' }}</td>
                        <td class="px-4 py-2">
                            <span class="px-1.5 py-0.5 text-xs rounded {{ $m['direction'] === 'in' ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400' }}">
                                {{ $m['direction'] }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $m['quantity'] }}</td>
                        <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $m['location']['name'] ?? '-' }}</td>
                        <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $m['user']['name'] ?? '-' }}</td>
                        <td class="px-4 py-2 text-gray-400 dark:text-gray-500">{{ \Carbon\Carbon::parse($m['created_at'])->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-4 text-sm text-gray-400 dark:text-gray-500">No recent stock movements.</div>
        @endif
    </div>
</div>
@endsection
