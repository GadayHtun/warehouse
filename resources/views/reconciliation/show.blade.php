@extends('layouts.app')

@section('title', 'Session #' . $session->id . ' Report')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reconciliation Report</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Session #{{ $session->id }} — {{ $session->location->name }} —
                <span class="px-1.5 py-0.5 text-xs rounded-full font-medium bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400">{{ $session->status }}</span>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('reports.reconciliation', $session) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Export PDF</a>
            <a href="{{ route('reconciliation.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Back</a>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 transition-colors">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Variance</div>
            <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $summary['total_variance_units'] }} units</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 transition-colors">
            <div class="text-sm text-gray-500 dark:text-gray-400">Absolute Variance</div>
            <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $summary['absolute_variance_units'] }} units</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 transition-colors">
            <div class="text-sm text-gray-500 dark:text-gray-400">Net Financial Impact</div>
            <div class="text-xl font-bold {{ $summary['net_financial_impact'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                ${{ number_format($summary['net_financial_impact'], 2) }}
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 transition-colors">
            <div class="text-sm text-gray-500 dark:text-gray-400">Lines Resolved</div>
            <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $summary['lines_resolved'] }} / {{ $summary['total_lines'] }}</div>
        </div>
    </div>

    {{-- Variance by Direction --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 transition-colors">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Positive Variances ({{ $summary['positive_variances_count'] }})</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Physical count exceeds system record — possible unrecorded stock-in</p>
            @foreach(array_slice($summary['variances_by_direction']['positive'], 0, 5) as $v)
                <div class="flex justify-between text-xs py-1 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-gray-900 dark:text-white">{{ $v['product_sku'] }}</span>
                    <span class="text-green-600 dark:text-green-400 font-mono">+{{ $v['variance'] }} ({{ number_format($v['variance_percentage'], 1) }}%)</span>
                </div>
            @endforeach
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 transition-colors">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Negative Variances ({{ $summary['negative_variances_count'] }})</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Physical count below system record — possible loss, theft, or unrecorded stock-out</p>
            @foreach(array_slice($summary['variances_by_direction']['negative'], 0, 5) as $v)
                <div class="flex justify-between text-xs py-1 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-gray-900 dark:text-white">{{ $v['product_sku'] }}</span>
                    <span class="text-red-600 dark:text-red-400 font-mono">{{ $v['variance'] }} ({{ number_format($v['variance_percentage'], 1) }}%)</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Full Line Detail --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden transition-colors">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">All Count Lines</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-xs text-gray-500 dark:text-gray-400 uppercase">
                <tr>
                    <th class="px-4 py-2">SKU</th>
                    <th class="px-4 py-2 text-right">Physical</th>
                    <th class="px-4 py-2 text-right">System</th>
                    <th class="px-4 py-2 text-right">Variance</th>
                    <th class="px-4 py-2 text-right">%</th>
                    <th class="px-4 py-2 text-right">$ Impact</th>
                    <th class="px-4 py-2">Resolution</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($summary['investigation_priority'] as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-4 py-2 font-medium text-xs text-gray-900 dark:text-white">{{ $item['product_sku'] }}</td>
                    <td class="px-4 py-2 text-right font-mono text-gray-900 dark:text-white">{{ $item['physical_quantity'] }}</td>
                    <td class="px-4 py-2 text-right font-mono text-gray-900 dark:text-white">{{ $item['system_quantity'] }}</td>
                    <td class="px-4 py-2 text-right font-mono {{ $item['variance'] > 0 ? 'text-green-600 dark:text-green-400' : ($item['variance'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white') }}">
                        {{ $item['variance'] > 0 ? '+' : '' }}{{ $item['variance'] }}
                    </td>
                    <td class="px-4 py-2 text-right text-gray-900 dark:text-white">{{ number_format($item['variance_percentage'], 1) }}%</td>
                    <td class="px-4 py-2 text-right font-mono text-gray-900 dark:text-white">${{ number_format($item['dollar_variance'], 2) }}</td>
                    <td class="px-4 py-2">
                        <span class="text-xs px-1 py-0.5 rounded
                            @switch($item['status'])
                                @case('resolved') bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 @break
                                @case('deferred') bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 @break
                                @case('flagged_recount') bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-400 @break
                                @default bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400
                            @endswitch">
                            {{ $item['resolution_type'] ?? $item['status'] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
