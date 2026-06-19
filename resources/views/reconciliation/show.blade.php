@extends('layouts.app')

@section('title', 'Session #' . $session->id . ' Report')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reconciliation Report</h1>
            <p class="text-sm text-gray-500">
                Session #{{ $session->id }} — {{ $session->location->name }} —
                <span class="px-1.5 py-0.5 text-xs rounded-full font-medium bg-green-100 text-green-700">{{ $session->status }}</span>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('reports.reconciliation', $session) }}" class="px-4 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">Export PDF</a>
            <a href="{{ route('reconciliation.index') }}" class="px-4 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">Back</a>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-lg border p-3">
            <div class="text-sm text-gray-500">Total Variance</div>
            <div class="text-xl font-bold">{{ $summary['total_variance_units'] }} units</div>
        </div>
        <div class="bg-white rounded-lg border p-3">
            <div class="text-sm text-gray-500">Absolute Variance</div>
            <div class="text-xl font-bold">{{ $summary['absolute_variance_units'] }} units</div>
        </div>
        <div class="bg-white rounded-lg border p-3">
            <div class="text-sm text-gray-500">Net Financial Impact</div>
            <div class="text-xl font-bold {{ $summary['net_financial_impact'] < 0 ? 'text-red-600' : 'text-green-600' }}">
                ${{ number_format($summary['net_financial_impact'], 2) }}
            </div>
        </div>
        <div class="bg-white rounded-lg border p-3">
            <div class="text-sm text-gray-500">Lines Resolved</div>
            <div class="text-xl font-bold">{{ $summary['lines_resolved'] }} / {{ $summary['total_lines'] }}</div>
        </div>
    </div>

    {{-- Variance by Direction --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-lg border p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Positive Variances ({{ $summary['positive_variances_count'] }})</h3>
            <p class="text-xs text-gray-500 mb-2">Physical count exceeds system record — possible unrecorded stock-in</p>
            @foreach(array_slice($summary['variances_by_direction']['positive'], 0, 5) as $v)
                <div class="flex justify-between text-xs py-1 border-b border-gray-100">
                    <span>{{ $v['product_sku'] }}</span>
                    <span class="text-green-600 font-mono">+{{ $v['variance'] }} ({{ number_format($v['variance_percentage'], 1) }}%)</span>
                </div>
            @endforeach
        </div>
        <div class="bg-white rounded-lg border p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Negative Variances ({{ $summary['negative_variances_count'] }})</h3>
            <p class="text-xs text-gray-500 mb-2">Physical count below system record — possible loss, theft, or unrecorded stock-out</p>
            @foreach(array_slice($summary['variances_by_direction']['negative'], 0, 5) as $v)
                <div class="flex justify-between text-xs py-1 border-b border-gray-100">
                    <span>{{ $v['product_sku'] }}</span>
                    <span class="text-red-600 font-mono">{{ $v['variance'] }} ({{ number_format($v['variance_percentage'], 1) }}%)</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Full Line Detail --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-700">All Count Lines</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
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
            <tbody class="divide-y divide-gray-100">
                @foreach($summary['investigation_priority'] as $item)
                <tr>
                    <td class="px-4 py-2 font-medium text-xs">{{ $item['product_sku'] }}</td>
                    <td class="px-4 py-2 text-right font-mono">{{ $item['physical_quantity'] }}</td>
                    <td class="px-4 py-2 text-right font-mono">{{ $item['system_quantity'] }}</td>
                    <td class="px-4 py-2 text-right font-mono {{ $item['variance'] > 0 ? 'text-green-600' : ($item['variance'] < 0 ? 'text-red-600' : '') }}">
                        {{ $item['variance'] > 0 ? '+' : '' }}{{ $item['variance'] }}
                    </td>
                    <td class="px-4 py-2 text-right">{{ number_format($item['variance_percentage'], 1) }}%</td>
                    <td class="px-4 py-2 text-right font-mono">${{ number_format($item['dollar_variance'], 2) }}</td>
                    <td class="px-4 py-2">
                        <span class="text-xs px-1 py-0.5 rounded
                            @switch($item['status'])
                                @case('resolved') bg-green-100 text-green-700 @break
                                @case('deferred') bg-gray-100 text-gray-600 @break
                                @case('flagged_recount') bg-purple-100 text-purple-700 @break
                                @default bg-yellow-100 text-yellow-700
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
