<!DOCTYPE html>
<html lang="en" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — Warehouse</title>
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
    <div class="min-h-screen flex">
        {{-- Left: Brand Panel --}}
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800">
            {{-- Decorative circles --}}
            <div class="absolute -top-20 -left-20 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-32 -right-32 w-[500px] h-[500px] bg-indigo-500/20 rounded-full blur-3xl"></div>

            <div class="relative z-10 flex flex-col justify-center px-16 text-white">
                {{-- Logo --}}
                <div class="mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/15 backdrop-blur-sm mb-6">
                        <span class="text-3xl">📦</span>
                    </div>
                    <h1 class="text-4xl font-bold mb-3">Warehouse</h1>
                    <p class="text-blue-100 text-lg">Inventory Control Platform</p>
                </div>

                {{-- Features --}}
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex-shrink-0 w-6 h-6 rounded-full bg-white/20 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium">Real-Time Anomaly Detection</p>
                            <p class="text-sm text-blue-200">8 autonomous checks catch issues before they compound</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex-shrink-0 w-6 h-6 rounded-full bg-white/20 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium">6-Stage Reconciliation</p>
                            <p class="text-sm text-blue-200">Gated pipeline with large-variance guardrails</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex-shrink-0 w-6 h-6 rounded-full bg-white/20 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium">Role-Based Access</p>
                            <p class="text-sm text-blue-200">Admin, Supervisor, and Agent tiers with audit logging</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Login Form --}}
        <div class="flex-1 flex flex-col">
            {{-- Mobile header --}}
            <div class="lg:hidden flex items-center justify-center py-8 bg-gradient-to-r from-blue-600 to-indigo-700">
                <div class="flex items-center gap-3 text-white">
                    <span class="text-2xl">📦</span>
                    <span class="text-xl font-bold">Warehouse</span>
                </div>
            </div>

            <div class="flex-1 flex items-center justify-center px-6 py-12">
                <div class="w-full max-w-md">
                    {{-- Dark mode toggle --}}
                    <div class="flex justify-end mb-6">
                        <button id="theme-toggle" type="button" class="p-2 rounded-lg text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="Toggle dark mode">
                            <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                            </svg>
                            <svg id="theme-toggle-dark-icon" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                            </svg>
                        </button>
                    </div>

                    {{-- Welcome text --}}
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Welcome back</h2>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">Sign in to your inventory platform</p>
                    </div>

                    {{-- Login form --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="mb-5">
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email address</label>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors @error('email') border-red-500 @enderror"
                                    placeholder="you@company.com">
                                @error('email')
                                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-5">
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                                <input id="password" type="password" name="password" required
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                                    placeholder="Enter your password">
                            </div>

                            <div class="flex items-center justify-between mb-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-brand-500 focus:ring-brand-500 bg-gray-50 dark:bg-gray-900">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                                </label>
                            </div>

                            <button type="submit" class="w-full py-2.5 px-4 bg-brand-500 hover:bg-brand-600 text-white font-medium rounded-xl focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200 shadow-sm hover:shadow">
                                Sign In
                            </button>
                        </form>
                    </div>

                    {{-- Demo credentials --}}
                    <div class="mt-6 bg-gray-100 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700/50">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Demo Credentials</p>
                        <div class="space-y-1.5 text-sm">
                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Admin</span>
                                <span class="font-mono text-xs">admin@warehouse.test</span>
                            </div>
                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Supervisor</span>
                                <span class="font-mono text-xs">supervisor@warehouse.test</span>
                            </div>
                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Agent</span>
                                <span class="font-mono text-xs">agent@warehouse.test</span>
                            </div>
                            <div class="pt-1 text-xs text-gray-400 dark:text-gray-500">
                                Password: <span class="font-mono">password</span>
                            </div>
                        </div>
                    </div>

                    <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-8">
                        Warehouse Inventory Control Platform
                    </p>
                </div>
            </div>
        </div>
    </div>

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
