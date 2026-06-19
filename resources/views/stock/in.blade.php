@extends('layouts.app')

@section('title', 'Stock In')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Record Stock In</h1>

    <form method="POST" action="{{ route('stock.in.store') }}" class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
        @csrf

        <div>
            <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
            <select id="product_id" name="product_id" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg @error('product_id') border-red-500 @enderror">
                <option value="">Select product...</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                        {{ $product->sku }} — {{ $product->name }}
                    </option>
                @endforeach
            </select>
            @error('product_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="location_id" class="block text-sm font-medium text-gray-700 mb-1">Location *</label>
            <select id="location_id" name="location_id" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg @error('location_id') border-red-500 @enderror">
                <option value="">Select location...</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                        {{ $location->name }} ({{ $location->code }})
                    </option>
                @endforeach
            </select>
            @error('location_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
            <input id="quantity" type="number" name="quantity" step="0.001" min="0.001" value="{{ old('quantity') }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg @error('quantity') border-red-500 @enderror">
            @error('quantity') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="unit_cost" class="block text-sm font-medium text-gray-700 mb-1">Unit Cost (optional)</label>
            <input id="unit_cost" type="number" name="unit_cost" step="0.01" min="0" value="{{ old('unit_cost') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>

        <div>
            <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">Supplier (optional)</label>
            <select id="supplier_id" name="supplier_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">No supplier</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="batch_lot" class="block text-sm font-medium text-gray-700 mb-1">Batch / Lot (optional)</label>
            <input id="batch_lot" type="text" name="batch_lot" value="{{ old('batch_lot') }}" maxlength="100"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>

        <div>
            <label for="reference_note" class="block text-sm font-medium text-gray-700 mb-1">Reference Note (optional)</label>
            <textarea id="reference_note" name="reference_note" rows="2" maxlength="500"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ old('reference_note') }}</textarea>
        </div>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700">Record Stock In</button>
            <a href="{{ route('stock.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
