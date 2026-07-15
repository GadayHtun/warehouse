@extends('layouts.app')

@section('title', 'Agent Findings')

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Agent Findings</h1>

    {{-- Severity counts --}}
    <div class="flex gap-3 flex-wrap">
        <a href="?status=open" class="px-3 py-1.5 text-sm rounded-lg border transition-colors {{ !request('status') || request('status') === 'open' ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-gray-900 dark:border-white' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
            Open ({{ ($counts['open_critical'] ?? 0) + ($counts['open_warning'] ?? 0) + ($counts['open_info'] ?? 0) }})
        </a>
        <a href="?severity=critical" class="px-3 py-1.5 text-sm rounded-lg border transition-colors {{ request('severity') === 'critical' ? 'bg-red-600 text-white border-red-600' : 'bg-white dark:bg-gray-800 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20' }}">
            Critical ({{ $counts['open_critical'] ?? 0 }})
        </a>
        <a href="?severity=warning" class="px-3 py-1.5 text-sm rounded-lg border transition-colors {{ request('severity') === 'warning' ? 'bg-yellow-600 text-white border-yellow-600' : 'bg-white dark:bg-gray-800 border-yellow-200 dark:border-yellow-800 text-yellow-700 dark:text-yellow-400 hover:bg-yellow-50 dark:hover:bg-yellow-900/20' }}">
            Warning ({{ $counts['open_warning'] ?? 0 }})
        </a>
        <a href="?severity=info" class="px-3 py-1.5 text-sm rounded-lg border transition-colors {{ request('severity') === 'info' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20' }}">
            Info ({{ $counts['open_info'] ?? 0 }})
        </a>
        <a href="?status=acknowledged" class="px-3 py-1.5 text-sm rounded-lg border transition-colors {{ request('status') === 'acknowledged' ? 'bg-green-600 text-white border-green-600' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
            Acknowledged ({{ $counts['acknowledged'] ?? 0 }})
        </a>
        <a href="?status=dismissed" class="px-3 py-1.5 text-sm rounded-lg border transition-colors {{ request('status') === 'dismissed' ? 'bg-gray-600 text-white border-gray-600' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
            Dismissed ({{ $counts['dismissed'] ?? 0 }})
        </a>
    </div>

    {{-- Findings Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden transition-colors">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-xs text-gray-500 dark:text-gray-400 uppercase">
                <tr>
                    <th class="px-4 py-3">Severity</th>
                    <th class="px-4 py-3">Check Type</th>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Product / Location</th>
                    <th class="px-4 py-3">Detected</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($findings as $finding)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $finding->severity === 'critical' ? 'bg-red-50 dark:bg-red-900/10' : '' }} transition-colors">
                    <td class="px-4 py-2">
                        <span class="px-1.5 py-0.5 text-xs rounded-full font-medium
                            @switch($finding->severity)
                                @case('critical') bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 @break
                                @case('warning') bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400 @break
                                @case('info') bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 @break
                            @endswitch">
                            {{ $finding->severity }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">{{ str_replace('_', ' ', $finding->check_type) }}</td>
                    <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">{{ $finding->title }}</td>
                    <td class="px-4 py-2 text-xs">
                        @if($finding->product)
                            <a href="#" class="text-brand-500 hover:underline">{{ $finding->product->sku }}</a>
                        @endif
                        @if($finding->location)
                            @if($finding->product) / @endif
                            <span class="text-gray-900 dark:text-white">{{ $finding->location->code }}</span>
                        @endif
                        @if(!$finding->product && !$finding->location)
                            <span class="text-gray-400 dark:text-gray-500">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-gray-400 dark:text-gray-500 text-xs">{{ $finding->detected_at->diffForHumans() }}</td>
                    <td class="px-4 py-2">
                        @if($finding->status === 'open')
                            <div class="flex gap-1">
                                <form method="POST" action="{{ route('findings.acknowledge', $finding) }}">
                                    @csrf
                                    <button class="text-xs text-green-600 dark:text-green-400 hover:underline">Acknowledge</button>
                                </form>
                                <button onclick="document.getElementById('dismiss-{{ $finding->id }}').classList.toggle('hidden')" class="text-xs text-red-600 dark:text-red-400 hover:underline">Dismiss</button>
                            </div>
                            <form id="dismiss-{{ $finding->id }}" method="POST" action="{{ route('findings.dismiss', $finding) }}" class="hidden mt-1">
                                @csrf
                                <input type="text" name="review_note" placeholder="Reason (min 10 chars)" minlength="10" required
                                    class="w-full text-xs px-1 py-0.5 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded mb-1 text-gray-900 dark:text-white focus:ring-1 focus:ring-brand-500 transition-colors">
                                <button class="text-xs text-red-600 dark:text-red-400 hover:underline">Confirm Dismiss</button>
                            </form>
                        @else
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $finding->status }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">No findings match these filters. The system is running smoothly.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $findings->links() }}
</div>
@endsection
