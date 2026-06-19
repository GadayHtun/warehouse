@extends('layouts.app')

@section('title', 'Agent Findings')

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">Agent Findings</h1>

    {{-- Severity counts --}}
    <div class="flex gap-3 flex-wrap">
        <a href="?status=open" class="px-3 py-1.5 text-sm rounded-lg border {{ !request('status') || request('status') === 'open' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white border-gray-300 hover:bg-gray-50' }}">
            Open ({{ ($counts['open_critical'] ?? 0) + ($counts['open_warning'] ?? 0) + ($counts['open_info'] ?? 0) }})
        </a>
        <a href="?severity=critical" class="px-3 py-1.5 text-sm rounded-lg border {{ request('severity') === 'critical' ? 'bg-red-600 text-white border-red-600' : 'bg-white border-red-200 text-red-700 hover:bg-red-50' }}">
            Critical ({{ $counts['open_critical'] ?? 0 }})
        </a>
        <a href="?severity=warning" class="px-3 py-1.5 text-sm rounded-lg border {{ request('severity') === 'warning' ? 'bg-yellow-600 text-white border-yellow-600' : 'bg-white border-yellow-200 text-yellow-700 hover:bg-yellow-50' }}">
            Warning ({{ $counts['open_warning'] ?? 0 }})
        </a>
        <a href="?severity=info" class="px-3 py-1.5 text-sm rounded-lg border {{ request('severity') === 'info' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-blue-200 text-blue-700 hover:bg-blue-50' }}">
            Info ({{ $counts['open_info'] ?? 0 }})
        </a>
        <a href="?status=acknowledged" class="px-3 py-1.5 text-sm rounded-lg border {{ request('status') === 'acknowledged' ? 'bg-green-600 text-white border-green-600' : 'bg-white border-gray-300 hover:bg-gray-50' }}">
            Acknowledged ({{ $counts['acknowledged'] ?? 0 }})
        </a>
        <a href="?status=dismissed" class="px-3 py-1.5 text-sm rounded-lg border {{ request('status') === 'dismissed' ? 'bg-gray-600 text-white border-gray-600' : 'bg-white border-gray-300 hover:bg-gray-50' }}">
            Dismissed ({{ $counts['dismissed'] ?? 0 }})
        </a>
    </div>

    {{-- Findings Table --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Severity</th>
                    <th class="px-4 py-3">Check Type</th>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Product / Location</th>
                    <th class="px-4 py-3">Detected</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($findings as $finding)
                <tr class="hover:bg-gray-50 {{ $finding->severity === 'critical' ? 'bg-red-50' : '' }}">
                    <td class="px-4 py-2">
                        <span class="px-1.5 py-0.5 text-xs rounded-full font-medium
                            @switch($finding->severity)
                                @case('critical') bg-red-100 text-red-700 @break
                                @case('warning') bg-yellow-100 text-yellow-700 @break
                                @case('info') bg-blue-100 text-blue-700 @break
                            @endswitch">
                            {{ $finding->severity }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-500">{{ str_replace('_', ' ', $finding->check_type) }}</td>
                    <td class="px-4 py-2 font-medium">{{ $finding->title }}</td>
                    <td class="px-4 py-2 text-xs">
                        @if($finding->product)
                            <a href="#" class="text-brand-500 hover:underline">{{ $finding->product->sku }}</a>
                        @endif
                        @if($finding->location)
                            @if($finding->product) / @endif
                            {{ $finding->location->code }}
                        @endif
                        @if(!$finding->product && !$finding->location)
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-gray-400 text-xs">{{ $finding->detected_at->diffForHumans() }}</td>
                    <td class="px-4 py-2">
                        @if($finding->status === 'open')
                            <div class="flex gap-1">
                                <form method="POST" action="{{ route('findings.acknowledge', $finding) }}">
                                    @csrf
                                    <button class="text-xs text-green-600 hover:underline">Acknowledge</button>
                                </form>
                                <button onclick="document.getElementById('dismiss-{{ $finding->id }}').classList.toggle('hidden')" class="text-xs text-red-600 hover:underline">Dismiss</button>
                            </div>
                            <form id="dismiss-{{ $finding->id }}" method="POST" action="{{ route('findings.dismiss', $finding) }}" class="hidden mt-1">
                                @csrf
                                <input type="text" name="review_note" placeholder="Reason (min 10 chars)" minlength="10" required
                                    class="w-full text-xs px-1 py-0.5 border rounded mb-1">
                                <button class="text-xs text-red-600 hover:underline">Confirm Dismiss</button>
                            </form>
                        @else
                            <span class="text-xs text-gray-400">{{ $finding->status }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">No findings match these filters. The system is running smoothly.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $findings->links() }}
</div>
@endsection
