<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SoftProject Hub') }}</title>

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

        <!-- Fonts (Outfit + Inter) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-800 dark:text-slate-200 antialiased h-full">
        <!-- Premium high-tech gradient background with glowing blur blobs -->
        <div class="min-h-screen flex flex-col justify-center items-center p-6 bg-slate-900 relative overflow-hidden select-none">
            <!-- Glowing aura blobs in background -->
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-sky-500 rounded-full blur-[140px] opacity-25"></div>
            <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-indigo-500 rounded-full blur-[140px] opacity-25"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-purple-500/10 rounded-full blur-[160px] pointer-events-none"></div>

            <div class="relative z-10 w-full flex flex-col items-center">
                <!-- Logo -->
                <div class="mb-8 transform hover:scale-105 transition-all duration-300">
                    <a href="/" class="flex flex-col items-center gap-3" wire:navigate>
                        <div class="p-3.5 rounded-2xl bg-gradient-to-tr from-sky-400 to-indigo-600 shadow-xl shadow-sky-500/10 ring-1 ring-white/10">
                            <x-application-logo class="w-12 h-12 fill-current text-white" />
                        </div>
                    </a>
                </div>

                <!-- Main Card -->
                <div class="w-full sm:max-w-md">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
