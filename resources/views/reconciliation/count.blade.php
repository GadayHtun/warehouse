@extends('layouts.app')

@section('title', 'Count — Session #' . $session->id)

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Physical Count</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Session #{{ $session->id }} —
                {{ $session->location->name }}
                @if($session->category_filter) ({{ $session->category_filter }}) @endif
                — Status: <span class="font-medium text-gray-900 dark:text-white">{{ str_replace('_', ' ', $session->status) }}</span>
            </p>
        </div>
        @if($session->countLines()->count() > 0)
        <form method="POST" action="{{ route('reconciliation.submit', $session) }}">
            @csrf
            <button type="submit" class="px-4 py-2 bg-brand-500 text-white text-sm font-medium rounded-lg hover:bg-brand-600 transition-colors">
                Submit & Calculate Variances
            </button>
        </form>
        @endif
    </div>

    {{-- Add Count Line --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 transition-colors">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Add Count Line</h2>
        <form method="POST" action="{{ route('reconciliation.add-count-line', $session) }}" class="flex gap-3 items-end">
            @csrf
            <div class="flex-1">
                <label for="product_id" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Product</label>
                <select id="product_id" name="product_id" required
                    class="w-full px-2 py-1.5 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors">
                    <option value="">Select...</option>
                    @foreach($products as $product)
                        @if(!isset($existingLines[$product->id]))
                            <option value="{{ $product->id }}">{{ $product->sku }} — {{ $product->name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="w-32">
                <label for="physical_quantity" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Physical Qty</label>
                <input id="physical_quantity" type="number" name="physical_quantity" step="0.001" min="0" required
                    class="w-full px-2 py-1.5 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors">
            </div>
            <button type="submit" class="px-4 py-1.5 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition-colors">Add</button>
        </form>
    </div>

    {{-- Existing Count Lines --}}
    @if($existingLines->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden transition-colors">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-xs text-gray-500 dark:text-gray-400 uppercase">
                <tr>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3 text-right">Physical Qty</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($existingLines as $line)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $line->product->sku }} — {{ $line->product->name }}</td>
                    <td class="px-4 py-2 text-right font-mono text-gray-900 dark:text-white">{{ $line->physical_quantity }}</td>
                    <td class="px-4 py-2 text-gray-400 dark:text-gray-500">{{ $line->status }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
