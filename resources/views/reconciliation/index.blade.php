@extends('layouts.app')

@section('title', 'Reconciliation')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reconciliation Sessions</h1>
        <a href="{{ route('reconciliation.create') }}" class="px-4 py-2 bg-brand-500 text-white text-sm font-medium rounded-lg hover:bg-brand-600 transition-colors">+ New Session</a>
    </div>

    @if($sessions->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden transition-colors">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-xs text-gray-500 dark:text-gray-400 uppercase">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Location</th>
                    <th class="px-4 py-3">Started By</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Started</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($sessions as $session)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-4 py-2 font-mono text-xs text-gray-900 dark:text-white">#{{ $session->id }}</td>
                    <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $session->location->name ?? '-' }}</td>
                    <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $session->user->name ?? '-' }}</td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-0.5 text-xs rounded-full font-medium
                            @switch($session->status)
                                @case('draft') bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 @break
                                @case('in_progress') bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 @break
                                @case('submitted') bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400 @break
                                @case('under_review') bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-400 @break
                                @case('closed') bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 @break
                            @endswitch">
                            {{ str_replace('_', ' ', $session->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-gray-400 dark:text-gray-500 text-xs">{{ $session->started_at?->diffForHumans() }}</td>
                    <td class="px-4 py-2">
                        @if($session->status === 'draft' || $session->status === 'in_progress')
                            <a href="{{ route('reconciliation.count', $session) }}" class="text-brand-500 hover:underline text-xs">Count →</a>
                        @elseif($session->status === 'submitted' || $session->status === 'under_review')
                            <a href="{{ route('reconciliation.review', $session) }}" class="text-brand-500 hover:underline text-xs">Review →</a>
                        @else
                            <a href="{{ route('reconciliation.show', $session) }}" class="text-brand-500 hover:underline text-xs">View</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-12 text-center transition-colors">
        <p class="text-gray-400 dark:text-gray-500">No active reconciliation sessions.</p>
        <a href="{{ route('reconciliation.create') }}" class="mt-2 inline-block text-brand-500 hover:underline text-sm">Start a new session</a>
    </div>
    @endif

    @if($closedSessions->isNotEmpty())
    <div class="mt-8">
        <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">Recently Closed</h2>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden transition-colors">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-xs text-gray-500 dark:text-gray-400 uppercase">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Location</th>
                        <th class="px-4 py-3">Closed</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($closedSessions as $session)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-4 py-2 font-mono text-xs text-gray-900 dark:text-white">#{{ $session->id }}</td>
                        <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $session->location->name ?? '-' }}</td>
                        <td class="px-4 py-2 text-gray-400 dark:text-gray-500 text-xs">{{ $session->closed_at?->diffForHumans() }}</td>
                        <td class="px-4 py-2">
                            <a href="{{ route('reconciliation.show', $session) }}" class="text-brand-500 hover:underline text-xs">View</a>
                            <a href="{{ route('reports.reconciliation', $session) }}" class="ml-2 text-gray-400 dark:text-gray-500 hover:underline text-xs">PDF</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
