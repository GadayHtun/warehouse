@extends('layouts.app')

@section('title', 'Stock')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Stock Overview</h1>
        <div class="flex gap-2">
            <a href="{{ route('stock.in.create') }}" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">+ Stock In</a>
            <a href="{{ route('stock.out.create') }}" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">− Stock Out</a>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">SKU</th>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">UoM</th>
                    <th class="px-4 py-3 text-right">Cost</th>
                    <th class="px-4 py-3 text-right">Retail</th>
                    <th class="px-4 py-3">Stock by Location</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($products as $product)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs">{{ $product->sku }}</td>
                    <td class="px-4 py-2 font-medium">{{ $product->name }}</td>
                    <td class="px-4 py-2 text-gray-500">{{ $product->category }}</td>
                    <td class="px-4 py-2 text-gray-500">{{ $product->unit_of_measure }}</td>
                    <td class="px-4 py-2 text-right">${{ number_format($product->cost_price, 2) }}</td>
                    <td class="px-4 py-2 text-right">${{ number_format($product->retail_price, 2) }}</td>
                    <td class="px-4 py-2">
                        <div class="flex flex-wrap gap-1">
                            @foreach($product->currentStock as $stock)
                                <span class="px-1.5 py-0.5 text-xs rounded border {{ $stock->quantity_on_hand < $product->reorder_point ? 'border-orange-300 bg-orange-50 text-orange-700' : 'border-gray-200 bg-gray-50 text-gray-600' }}">
                                    {{ optional($locations->firstWhere('id', $stock->location_id))->code ?? 'LOC-'.$stock->location_id }}: {{ $stock->quantity_on_hand }}
                                </span>
                            @endforeach
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No products found. Run seeders first.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
