@extends('layouts.app')

@section('title', 'Finding #' . $finding->id)

@section('content')
<div class="max-w-3xl mx-auto space-y-4">
    <a href="{{ route('findings.index') }}" class="text-sm text-gray-500 hover:underline">← Back to findings</a>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center gap-2 mb-4">
            <span class="px-2 py-0.5 text-xs rounded-full font-medium
                @switch($finding->severity)
                    @case('critical') bg-red-100 text-red-700 @break
                    @case('warning') bg-yellow-100 text-yellow-700 @break
                    @case('info') bg-blue-100 text-blue-700 @break
                @endswitch">
                {{ $finding->severity }}
            </span>
            <span class="px-2 py-0.5 text-xs rounded-full font-medium
                @switch($finding->status)
                    @case('open') bg-yellow-100 text-yellow-700 @break
                    @case('acknowledged') bg-green-100 text-green-700 @break
                    @case('dismissed') bg-gray-100 text-gray-600 @break
                @endswitch">
                {{ $finding->status }}
            </span>
            <span class="text-xs text-gray-400">{{ $finding->check_type }}</span>
        </div>

        <h1 class="text-xl font-bold text-gray-900 mb-2">{{ $finding->title }}</h1>
        <p class="text-gray-600">{{ $finding->description }}</p>

        <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
            @if($finding->product)
            <div>
                <span class="text-gray-500">Product:</span>
                <span class="font-medium">{{ $finding->product->sku }} — {{ $finding->product->name }}</span>
            </div>
            @endif
            @if($finding->location)
            <div>
                <span class="text-gray-500">Location:</span>
                <span class="font-medium">{{ $finding->location->name }} ({{ $finding->location->code }})</span>
            </div>
            @endif
            <div>
                <span class="text-gray-500">Detected at:</span>
                <span class="font-medium">{{ $finding->detected_at->toDateTimeString() }}</span>
            </div>
            @if($finding->reviewed_at)
            <div>
                <span class="text-gray-500">Reviewed at:</span>
                <span class="font-medium">{{ $finding->reviewed_at->toDateTimeString() }}</span>
            </div>
            <div>
                <span class="text-gray-500">Reviewer:</span>
                <span class="font-medium">{{ $finding->reviewer->name ?? '-' }}</span>
            </div>
            @endif
        </div>

        @if($finding->review_note)
        <div class="mt-4 p-3 bg-gray-50 rounded border text-sm">
            <span class="text-gray-500">Review Note:</span> {{ $finding->review_note }}
        </div>
        @endif
    </div>
</div>
@endsection
