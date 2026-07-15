@extends('layouts.app')

@section('title', 'Finding #' . $finding->id)

@section('content')
<div class="max-w-3xl mx-auto space-y-4">
    <a href="{{ route('findings.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:underline">← Back to findings</a>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 transition-colors">
        <div class="flex items-center gap-2 mb-4">
            <span class="px-2 py-0.5 text-xs rounded-full font-medium
                @switch($finding->severity)
                    @case('critical') bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 @break
                    @case('warning') bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400 @break
                    @case('info') bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 @break
                @endswitch">
                {{ $finding->severity }}
            </span>
            <span class="px-2 py-0.5 text-xs rounded-full font-medium
                @switch($finding->status)
                    @case('open') bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400 @break
                    @case('acknowledged') bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 @break
                    @case('dismissed') bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 @break
                @endswitch">
                {{ $finding->status }}
            </span>
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $finding->check_type }}</span>
        </div>

        <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $finding->title }}</h1>
        <p class="text-gray-600 dark:text-gray-300">{{ $finding->description }}</p>

        <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
            @if($finding->product)
            <div>
                <span class="text-gray-500 dark:text-gray-400">Product:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $finding->product->sku }} — {{ $finding->product->name }}</span>
            </div>
            @endif
            @if($finding->location)
            <div>
                <span class="text-gray-500 dark:text-gray-400">Location:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $finding->location->name }} ({{ $finding->location->code }})</span>
            </div>
            @endif
            <div>
                <span class="text-gray-500 dark:text-gray-400">Detected at:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $finding->detected_at->toDateTimeString() }}</span>
            </div>
            @if($finding->reviewed_at)
            <div>
                <span class="text-gray-500 dark:text-gray-400">Reviewed at:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $finding->reviewed_at->toDateTimeString() }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Reviewer:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $finding->reviewer->name ?? '-' }}</span>
            </div>
            @endif
        </div>

        @if($finding->review_note)
        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-900/50 rounded border border-gray-200 dark:border-gray-700 text-sm transition-colors">
            <span class="text-gray-500 dark:text-gray-400">Review Note:</span> <span class="text-gray-900 dark:text-white">{{ $finding->review_note }}</span>
        </div>
        @endif
    </div>
</div>
@endsection
