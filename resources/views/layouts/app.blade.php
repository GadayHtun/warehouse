<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Warehouse') — Inventory System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#eff6ff', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    @auth
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14">
                <div class="flex space-x-6">
                    <a href="{{ route('dashboard') }}" class="flex items-center font-bold text-gray-900 text-lg">
                        📦 Warehouse
                    </a>
                    <div class="flex space-x-1">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 {{ request()->routeIs('dashboard') ? 'border-b-2 border-brand-500 text-gray-900' : '' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('stock.index') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 {{ request()->routeIs('stock.*') ? 'border-b-2 border-brand-500 text-gray-900' : '' }}">
                            Stock
                        </a>
                        @if(Auth::user()?->hasRole(['supervisor', 'admin']))
                        <a href="{{ route('reconciliation.index') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 {{ request()->routeIs('reconciliation.*') ? 'border-b-2 border-brand-500 text-gray-900' : '' }}">
                            Reconciliation
                        </a>
                        <a href="{{ route('findings.index') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 {{ request()->routeIs('findings.*') ? 'border-b-2 border-brand-500 text-gray-900' : '' }}">
                            Findings
                            @php
                                $openCritical = \App\Models\AgentFinding::open()->bySeverity('critical')->count();
                            @endphp
                            @if($openCritical > 0)
                                <span class="ml-1 px-1.5 py-0.5 text-xs font-bold bg-red-500 text-white rounded-full">{{ $openCritical }}</span>
                            @endif
                        </a>
                        @endif
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-500">{{ Auth::user()->name }} ({{ ucfirst(Auth::user()->role) }})</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    @endauth

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if(session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if(session('warning'))
            <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-lg text-sm">{{ session('warning') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
