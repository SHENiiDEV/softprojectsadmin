<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Project Manager Hub') }}</title>

        <!-- PWA & Mobile Web App Meta -->
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="SoftProject">
        <meta name="theme-color" content="#0284c7" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#0b0f19" media="(prefers-color-scheme: dark)">

        <!-- Manifest & Favicons -->
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

        <!-- PWA Service Worker Registration -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js').catch(function(err) {
                        console.log('PWA ServiceWorker registration failed: ', err);
                    });
                });
            }
        </script>

        <!-- Mobile & Safe-Area Layout Styling -->
        <style>
            @media (max-width: 767px) {
                .mobile-header-safe {
                    padding-top: max(env(safe-area-inset-top, 0px), 14px) !important;
                    min-height: calc(4rem + max(env(safe-area-inset-top, 0px), 14px)) !important;
                }
                .mobile-drawer-safe {
                    padding-top: max(env(safe-area-inset-top, 0px), 14px) !important;
                }
            }
        </style>

        <!-- Fonts (Outfit + Inter) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <!-- Font Awesome Free icons CDN -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

        <!-- Prevent Flash of Light/Dark Theme -->
        <script>
            function applyTheme() {
                if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
            applyTheme();
            document.addEventListener('livewire:navigated', applyTheme);
        </script>

        <!-- FullCalendar -->
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js" data-navigate-once></script>

        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js" data-navigate-once></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="h-full font-sans antialiased text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-950" x-data="{ sidebarOpen: false }">
        <div class="flex h-screen h-[100dvh] overflow-hidden">
            <!-- Sidebar (Desktop) -->
            <aside class="hidden md:flex md:flex-col md:w-64 bg-white dark:bg-slate-900 border-r border-slate-200/60 dark:border-slate-800 flex-shrink-0">
                <!-- Sidebar Header/Logo -->
                <div class="flex items-center h-16 px-6 bg-slate-50/50 dark:bg-slate-950/40 border-b border-slate-200/60 dark:border-slate-800/60">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2.5">
                        @if(\App\Models\Setting::get('app_logo_path'))
                            <img class="h-9 w-9 object-contain rounded-xl shadow-md p-0.5 bg-white" src="{{ asset('storage/' . \App\Models\Setting::get('app_logo_path')) }}" alt="Logo">
                        @else
                            <span class="p-2 rounded-xl bg-gradient-to-tr from-sky-400 to-indigo-600 text-white font-extrabold text-lg shadow-lg shadow-sky-500/20">
                                SP
                            </span>
                        @endif
                        <span class="font-outfit font-bold text-lg tracking-wide text-slate-800 dark:text-white">{{ config('app.name', 'SoftProject Hub') }}</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <x-app-navigation />

                <!-- User Profile & Logout -->
                <div class="p-4 border-t border-slate-200/60 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-950/20">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('profile') }}" class="flex items-center space-x-3 overflow-hidden group hover:opacity-80 transition-opacity" wire:navigate>
                            <img class="h-9 w-9 rounded-xl object-cover shadow-md shadow-sky-500/10 transition-transform duration-200 group-hover:scale-105" src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}">
                            <div class="truncate">
                                <p class="text-sm font-semibold text-slate-700 dark:text-white leading-tight group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">{{ auth()->user()->name }}</p>
                                <span class="text-xs text-slate-400 dark:text-indigo-400 capitalize">{{ auth()->user()->roles->first()?->name ?? 'User' }}</span>
                            </div>
                        </a>
                        <livewire:layout.navigation />
                    </div>
                </div>
            </aside>

            <!-- Sidebar (Mobile Drawer) -->
            <div x-show="sidebarOpen" class="fixed inset-0 z-50 md:hidden" style="display: none;">
                <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="sidebarOpen = false"></div>

                <div x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative flex flex-col w-full max-w-xs h-full bg-white dark:bg-slate-900 border-r border-slate-200/60 dark:border-slate-800 mobile-drawer-safe">
                    <!-- Mobile Close Button -->
                    <div class="absolute top-0 right-0 -mr-12 pt-4 mobile-drawer-safe">
                        <button type="button" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" @click="sidebarOpen = false">
                            <svg class="h-6 w-6 text-slate-500 dark:text-white hover:text-slate-800 dark:hover:text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Sidebar Logo -->
                    <div class="flex items-center h-16 px-6 bg-slate-50/50 dark:bg-slate-950/40 border-b border-slate-200/60 dark:border-slate-800/60">
                        <a href="{{ route('dashboard') }}" class="flex items-center space-x-2.5">
                            @if(\App\Models\Setting::get('app_logo_path'))
                                <img class="h-9 w-9 object-contain rounded-xl shadow-md p-0.5 bg-white" src="{{ asset('storage/' . \App\Models\Setting::get('app_logo_path')) }}" alt="Logo">
                            @else
                                <span class="p-2 rounded-xl bg-gradient-to-tr from-sky-400 to-indigo-600 text-white font-extrabold text-lg shadow-lg">
                                    PM
                                </span>
                            @endif
                            <span class="font-outfit font-bold text-lg tracking-wide text-slate-800 dark:text-white">{{ config('app.name', 'SoftProject Hub') }}</span>
                        </a>
                    </div>

                    <!-- Mobile Navigation -->
                    <x-app-navigation variant="drawer" />

                    <!-- Mobile User Profile -->
                    <div class="p-4 border-t border-slate-200/60 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-950/20">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('profile') }}" class="flex items-center space-x-3 overflow-hidden group hover:opacity-80 transition-opacity" wire:navigate>
                                <img class="h-9 w-9 rounded-xl object-cover shadow-md shadow-sky-500/10 transition-transform duration-200 group-hover:scale-105" src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}">
                                <div class="truncate">
                                    <p class="text-sm font-semibold text-slate-700 dark:text-white leading-tight group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">{{ auth()->user()->name }}</p>
                                    <span class="text-xs text-slate-400 dark:text-indigo-400 capitalize">{{ auth()->user()->roles->first()?->name ?? 'User' }}</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="flex flex-col flex-1 w-0 overflow-hidden">
                <!-- Top Header -->
                <header class="flex items-center justify-between h-16 px-4 sm:px-6 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 flex-shrink-0 mobile-header-safe">
                    <div class="flex items-center space-x-4">
                        <button type="button" class="p-2 -ml-2 rounded-xl text-slate-500 dark:text-slate-400 md:hidden hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none" @click="sidebarOpen = true">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        @if (isset($header))
                            <div class="font-outfit font-bold text-lg md:text-xl text-slate-700 dark:text-slate-200 select-none">
                                {{ $header }}
                            </div>
                        @endif
                        <div>
                            <livewire:global-search />
                        </div>
                    </div>

                    <!-- Header Right Actions: Theme Toggle -->
                    <div class="flex items-center space-x-3">
                        <livewire:notification-tray />
                        <div x-data="{
                            darkMode: localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
                        }" x-init="
                            $watch('darkMode', val => {
                                if (val) {
                                    document.documentElement.classList.add('dark');
                                    localStorage.setItem('color-theme', 'dark');
                                } else {
                                    document.documentElement.classList.remove('dark');
                                    localStorage.setItem('color-theme', 'light');
                                }
                            });
                            if (darkMode) {
                                document.documentElement.classList.add('dark');
                            } else {
                                document.documentElement.classList.remove('dark');
                            }
                        ">
                            <button @click="darkMode = !darkMode" type="button" class="p-2.5 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none" title="Toggle theme">
                                <!-- Sun icon -->
                                <svg x-show="darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                                </svg>
                                <!-- Moon icon -->
                                <svg x-show="!darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </header>

                <!-- Page Main Content -->
                <main class="flex-1 relative overflow-y-auto focus:outline-none bg-slate-50 dark:bg-slate-950 {{ request()->routeIs('tasks.kanban') ? 'p-4 md:p-6 pb-24 md:pb-6' : 'p-6 md:p-8 pb-28 md:pb-8' }}">
                    <div class="{{ request()->routeIs('tasks.kanban') ? 'w-full' : 'max-w-7xl mx-auto' }} space-y-6">
                        @if(auth()->check() && !auth()->user()->telegram_id)
                            <div class="p-4 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900 rounded-2xl flex items-center justify-between shadow-sm">
                                <div class="flex items-center space-x-3">
                                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                                            {{ __('Telegram notifications not configured') }}
                                        </p>
                                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                                            {{ __('Link your Telegram account in profile settings to receive instant notifications about tasks and report deadlines.') }}
                                        </p>
                                    </div>
                                </div>
                                <a href="{{ route('profile') }}" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold text-amber-800 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/40 hover:bg-amber-200 dark:hover:bg-amber-900/60 rounded-xl transition duration-200" wire:navigate>
                                    {{ __('Configure') }}
                                </a>
                            </div>
                        @endif

                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        <!-- Mobile Bottom Navigation Bar (PWA) -->
        <x-app-navigation variant="bottom" />

        @livewireScripts
    </body>
</html>
