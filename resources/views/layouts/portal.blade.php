<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Client Support Portal</title>

        <!-- Fonts (Plus Jakarta Sans + Outfit) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        
        <!-- Font Awesome Free icons CDN -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

        <!-- Prevent Flash of Light/Dark Theme -->
        <script>
            function applyTheme() {
                if (localStorage.getItem('color-theme') === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
            applyTheme();
            document.addEventListener('livewire:navigated', applyTheme);
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        
        <style>
            body {
                font-family: 'Plus Jakarta Sans', sans-serif;
            }
            .font-outfit {
                font-family: 'Outfit', sans-serif;
            }
            .glass-panel {
                background: rgba(255, 255, 255, 0.75);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(0, 0, 0, 0.06);
            }
            .dark .glass-panel {
                background: rgba(15, 23, 42, 0.5);
                border: 1px solid rgba(255, 255, 255, 0.08);
            }
            @keyframes fade-in-up {
                from { opacity: 0; transform: translateY(12px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .animate-fade-in-up {
                animation: fade-in-up 0.4s ease-out both;
            }
            .animate-fade-in-up-delay-1 { animation-delay: 0.05s; }
            .animate-fade-in-up-delay-2 { animation-delay: 0.1s; }
            .animate-fade-in-up-delay-3 { animation-delay: 0.15s; }
            .animate-fade-in-up-delay-4 { animation-delay: 0.2s; }
            @keyframes fade-in {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            .animate-fade-in {
                animation: fade-in 0.3s ease-out both;
            }
            /* Custom scrollbar */
            .custom-scroll::-webkit-scrollbar { width: 4px; }
            .custom-scroll::-webkit-scrollbar-track { background: transparent; }
            .custom-scroll::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.3); border-radius: 999px; }
            .dark .custom-scroll::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); }
            /* Priority stripe */
            .priority-stripe-low { border-left: 3px solid #22c55e; }
            .priority-stripe-medium { border-left: 3px solid #0ea5e9; }
            .priority-stripe-high { border-left: 3px solid #f97316; }
            .priority-stripe-critical { border-left: 3px solid #ef4444; }
            /* Skeleton loading */
            @keyframes skeleton-pulse {
                0%, 100% { opacity: 0.4; }
                50% { opacity: 0.7; }
            }
            .skeleton {
                animation: skeleton-pulse 1.5s ease-in-out infinite;
                background: linear-gradient(90deg, rgba(148,163,184,0.1) 25%, rgba(148,163,184,0.2) 50%, rgba(148,163,184,0.1) 75%);
                border-radius: 8px;
            }
        </style>
    </head>
    <body class="h-full bg-slate-50 dark:bg-[#020617] text-slate-800 dark:text-[#F8FAFC] antialiased overflow-y-auto selection:bg-indigo-500/30">
        <!-- Ambient Background Gradients -->
        <div class="fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -top-[40%] -left-[20%] w-[80%] h-[80%] rounded-full bg-indigo-200/40 dark:bg-indigo-900/20 blur-[120px]"></div>
            <div class="absolute -bottom-[40%] -right-[20%] w-[80%] h-[80%] rounded-full bg-sky-200/30 dark:bg-sky-900/15 blur-[120px]"></div>
        </div>

        <div class="min-h-screen flex flex-col justify-between">
            <!-- Header -->
            <header class="w-full border-b border-slate-200/60 dark:border-white/5 bg-white/50 dark:bg-slate-950/30 backdrop-blur-xl">
                <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <span class="p-2.5 rounded-xl bg-gradient-to-tr from-sky-400 to-indigo-600 text-white font-extrabold text-lg shadow-lg shadow-sky-500/25">
                            SP
                        </span>
                        <div>
                            <span class="font-outfit font-extrabold text-lg tracking-wide text-slate-900 dark:text-white block leading-tight">SoftProject</span>
                            <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Support Portal</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <!-- Theme Toggle -->
                        <div x-data="{ darkMode: localStorage.getItem('color-theme') === 'dark' }" x-init="$watch('darkMode', val => { document.documentElement.classList.toggle('dark', val); localStorage.setItem('color-theme', val ? 'dark' : 'light'); }); document.documentElement.classList.toggle('dark', darkMode);">
                            <button @click="darkMode = !darkMode" type="button" class="p-2.5 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors focus:outline-none" title="Toggle theme">
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
                        <div class="hidden sm:flex items-center space-x-2 bg-slate-100 dark:bg-white/5 px-3 py-2 rounded-xl border border-slate-200/60 dark:border-white/5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Online</span>
                        </div>
                    </div>
                </div>
                <!-- Accent gradient stripe -->
                <div class="h-[2px] bg-gradient-to-r from-sky-400 via-indigo-500 to-violet-500"></div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 py-6">
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="w-full py-5 text-center text-xs text-slate-400 dark:text-slate-600 border-t border-slate-200/60 dark:border-white/5">
                <p>&copy; {{ date('Y') }} SoftProject Hub. All rights reserved.</p>
            </footer>
        </div>

        @livewireScripts
    </body>
</html>
