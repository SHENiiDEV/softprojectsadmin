<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found - 404</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-950 flex flex-col items-center justify-center p-6">
    <div class="max-w-md w-full text-center space-y-6">
        <div class="relative">
            <h1 class="font-outfit font-extrabold text-[120px] leading-none text-slate-200 dark:text-slate-900 tracking-tighter select-none">404</h1>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="px-4 py-1 rounded-full bg-sky-50 dark:bg-sky-950/40 border border-sky-100 dark:border-sky-900/40 text-[10px] font-bold text-sky-600 dark:text-sky-400 uppercase tracking-widest mt-12 shadow-sm">Page Not Found</span>
            </div>
        </div>

        <div class="space-y-2">
            <h2 class="font-outfit font-bold text-2xl text-slate-800 dark:text-white tracking-tight">Lost in Space?</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable or disabled by feature flag.</p>
        </div>

        <div class="pt-4">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-6 py-3 text-sm font-semibold text-white bg-gradient-to-tr from-sky-400 to-indigo-600 hover:from-sky-500 hover:to-indigo-700 rounded-xl shadow-lg shadow-sky-500/20 hover:-translate-y-0.5 transition-all duration-200">
                Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
