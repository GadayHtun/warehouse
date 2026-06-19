@extends('layouts.app')

@section('title', 'New Reconciliation')

@section('content')
<div class="max-w-xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">New Reconciliation Session</h1>

    <form method="POST" action="{{ route('reconciliation.store') }}" class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
        @csrf

        <div>
            <label for="location_id" class="block text-sm font-medium text-gray-700 mb-1">Location *</label>
            <select id="location_id" name="location_id" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg @error('location_id') border-red-500 @enderror">
                <option value="">Select location to reconcile...</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                        {{ $location->name }} ({{ $location->code }}) — {{ $location->type }}
                    </option>
                @endforeach
            </select>
            @error('location_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="category_filter" class="block text-sm font-medium text-gray-700 mb-1">Category Filter (optional)</label>
            <select id="category_filter" name="category_filter"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" {{ old('category_filter') == $category ? 'selected' : '' }}>
                        {{ $category }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">Limit this reconciliation to a specific product category</p>
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
            <textarea id="notes" name="notes" rows="2" maxlength="1000"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ old('notes') }}</textarea>
        </div>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="px-4 py-2 bg-brand-500 text-white font-medium rounded-lg hover:bg-brand-600">Create Session</button>
            <a href="{{ route('reconciliation.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
