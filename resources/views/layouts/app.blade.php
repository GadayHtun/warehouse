<!DOCTYPE html>
<html lang="en" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Warehouse') — Inventory System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#eff6ff', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8' }
                    }
                }
            }
        }
    </script>
    <script>
        // Dark mode: apply saved preference or system default immediately (before paint)
        (function() {
            const stored = localStorage.getItem('theme');
            if (stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen transition-colors duration-200">
    @auth
    <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14">
                <div class="flex space-x-6">
                    <a href="{{ route('dashboard') }}" class="flex items-center font-bold text-gray-900 dark:text-white text-lg">
                        📦 Warehouse
                    </a>
                    <div class="flex space-x-1">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white {{ request()->routeIs('dashboard') ? 'border-b-2 border-brand-500 text-gray-900 dark:text-white' : '' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('stock.index') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white {{ request()->routeIs('stock.*') ? 'border-b-2 border-brand-500 text-gray-900 dark:text-white' : '' }}">
                            Stock
                        </a>
                        @if(Auth::user()?->hasRole(['supervisor', 'admin']))
                        <a href="{{ route('reconciliation.index') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white {{ request()->routeIs('reconciliation.*') ? 'border-b-2 border-brand-500 text-gray-900 dark:text-white' : '' }}">
                            Reconciliation
                        </a>
                        <a href="{{ route('findings.index') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white {{ request()->routeIs('findings.*') ? 'border-b-2 border-brand-500 text-gray-900 dark:text-white' : '' }}">
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
                    {{-- Dark mode toggle --}}
                    <button id="theme-toggle" type="button" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Toggle dark mode">
                        {{-- Sun icon (shown in dark mode) --}}
                        <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                        {{-- Moon icon (shown in light mode) --}}
                        <svg id="theme-toggle-dark-icon" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                        </svg>
                    </button>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ Auth::user()->name }} ({{ ucfirst(Auth::user()->role) }})</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    @endauth

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if(session('success'))
            <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 rounded-lg text-sm transition-colors">{{ session('success') }}</div>
        @endif
        @if(session('warning'))
            <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 text-yellow-700 dark:text-yellow-300 rounded-lg text-sm transition-colors">{{ session('warning') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-lg text-sm transition-colors">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>

    <script>
        // Dark mode toggle
        const toggle = document.getElementById('theme-toggle');
        const lightIcon = document.getElementById('theme-toggle-light-icon');
        const darkIcon = document.getElementById('theme-toggle-dark-icon');

        function updateIcons() {
            if (document.documentElement.classList.contains('dark')) {
                lightIcon.classList.remove('hidden');
                darkIcon.classList.add('hidden');
            } else {
                lightIcon.classList.add('hidden');
                darkIcon.classList.remove('hidden');
            }
        }

        updateIcons();

        toggle.addEventListener('click', function() {
            document.documentElement.classList.toggle('dark');
            const isDark = document.documentElement.classList.contains('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateIcons();
        });

        // Listen for system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            if (!localStorage.getItem('theme')) {
                document.documentElement.classList.toggle('dark', e.matches);
                updateIcons();
            }
        });

        // Form submission loading states
        document.addEventListener('submit', function(e) {
            var btn = e.target.querySelector('[type="submit"]');
            if (!btn || btn.dataset.loading) return;
            btn.dataset.loading = '1';
            btn.dataset.originalText = btn.innerHTML;
            btn.disabled = true;
            btn.classList.add('opacity-60', 'cursor-not-allowed');
            btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Submitting…';
        });
        window.addEventListener('pagehide', function() {
            document.querySelectorAll('[data-loading]').forEach(function(btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-60', 'cursor-not-allowed');
                btn.innerHTML = btn.dataset.originalText;
                delete btn.dataset.loading;
            });
        });
    </script>
</body>
</html>
