<div class="space-y-8" x-data="{ activeTab: new URLSearchParams(window.location.search).get('tab') || '{{ config('features.credential_vault', true) ? 'credentials' : (config('features.websites_tab', true) ? 'websites' : (config('features.compliance_tab', true) ? 'boarding' : (config('features.reports_tab', true) ? 'reports' : (config('features.notes_tab', true) ? 'notes' : 'changelog')))) }}' }">
    <x-slot name="header">
        View Company: {{ $project->name }}
    </x-slot>

    <!-- Top Action Bar / Breadcrumbs -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800/60">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                <a href="{{ route('projects.index') }}" class="hover:text-sky-600 dark:hover:text-sky-400 transition-colors" wire:navigate>Company Directory</a>
                <span>/</span>
                <span class="text-slate-500 dark:text-slate-300">Details</span>
            </div>
            <h1 class="font-outfit font-extrabold text-3xl text-slate-700 dark:text-white tracking-tight mt-1">{{ $project->name }}</h1>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('projects.index') }}" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm transition-all duration-200" wire:navigate>
                Back to Directory
            </a>
            @if(config('features.pdf_export', true))
            <a href="{{ route('projects.pdf', $project->id) }}" target="_blank" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/20 dark:text-rose-400 border border-rose-200/40 dark:border-rose-900/40 rounded-xl shadow-sm hover:-translate-y-0.5 transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export PDF
            </a>
            @endif
            <a href="{{ route('projects.edit', $project->id) }}" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-sky-700 bg-sky-100 hover:bg-sky-200 dark:bg-sky-950/40 dark:text-sky-400 border border-sky-200/40 dark:border-sky-900/40 rounded-xl shadow-sm hover:-translate-y-0.5 transition-all duration-200" wire:navigate>
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Edit Details
            </a>
            @if(config('features.notes_tab', true))
            <button wire:click="openNoteModal" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-200/40 dark:border-amber-900/40 rounded-xl shadow-sm hover:-translate-y-0.5 transition-all duration-200">
                <i class="fa-solid fa-note-sticky mr-2 text-xs"></i>
                Add Note
            </button>
            @endif
        </div>
    </div>

    <!-- Company Status Badges & Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Project Status Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm flex items-center space-x-4">
            <div class="p-3 rounded-xl 
                @if($project->status === 'active') bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400
                @elseif($project->status === 'onboarding') bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400
                @else bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400 @endif">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
            </div>
            <div>
                <span class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Project Status</span>
                <span class="inline-flex items-center text-sm font-bold uppercase tracking-wider mt-0.5
                    @if($project->status === 'active') text-emerald-700 dark:text-emerald-400
                    @elseif($project->status === 'onboarding') text-amber-700 dark:text-amber-400
                    @else text-rose-700 dark:text-rose-400 @endif">
                    {{ $project->status }}
                </span>
            </div>
        </div>

        <!-- Integration Status Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm flex items-center space-x-4">
            <div class="p-3 rounded-xl 
                @if($project->integration_status === 'completed') bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400
                @elseif($project->integration_status === 'in_progress') bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400
                @else bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 @endif">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                </svg>
            </div>
            <div>
                <span class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Integration Status</span>
                <span class="inline-flex items-center text-sm font-bold uppercase tracking-wider mt-0.5
                    @if($project->integration_status === 'completed') text-emerald-700 dark:text-emerald-400
                    @elseif($project->integration_status === 'in_progress') text-amber-700 dark:text-amber-400
                    @else text-zinc-600 dark:text-zinc-400 @endif">
                    {{ $project->integration_status ?: 'Pending' }}
                </span>
            </div>
        </div>

        <!-- Manager Info Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm flex items-center space-x-4">
            <div class="p-3 rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/20 dark:text-indigo-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <div>
                <span class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Project Manager</span>
                <span class="block text-sm font-bold text-slate-700 dark:text-slate-300 mt-0.5 truncate max-w-[150px]">
                    {{ $project->manager?->name ?: 'Not assigned' }}
                </span>
            </div>
        </div>

        <!-- UBO Info Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm flex items-center space-x-4">
            <div class="p-3 rounded-xl bg-sky-50 text-sky-700 dark:bg-sky-950/20 dark:text-sky-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div>
                <span class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Beneficiary (UBO)</span>
                <span class="block text-sm font-bold text-slate-700 dark:text-slate-300 mt-0.5 truncate max-w-[150px]">
                    {{ $project->ubo ?: 'Not specified' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Company & Director Detail Split -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left 2 Cols: Main Company Profile -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-6">
                <h2 class="font-outfit font-bold text-lg text-slate-700 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800/40 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Company Details & Credentials
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                    <!-- Websites -->
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Company Websites</span>
                        <div class="flex flex-col space-y-1">
                            @forelse($project->websites as $web)
                                <a href="{{ $web->url }}" target="_blank" class="inline-flex items-center text-sm font-medium text-sky-600 hover:text-sky-750 dark:text-sky-400 dark:hover:text-sky-300 break-all">
                                    {{ $web->name }}: {{ $web->url }}
                                    <svg class="h-3.5 w-3.5 ml-1.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            @empty
                                <span class="text-sm text-slate-500 dark:text-slate-400">-</span>
                            @endforelse
                        </div>
                    </div>

                    <!-- MCC Code -->
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">MCC Code (Industry)</span>
                        <span class="text-sm font-mono text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-950 px-2 py-1 rounded border border-slate-100 dark:border-slate-800">
                            {{ $project->mcc ?: 'Not specified' }}
                        </span>
                    </div>

                    <!-- Phone Contacts -->
                    <div class="md:col-span-2">
                        <span class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Contact Phones</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Krisp -->
                            <div class="p-3 bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/60 rounded-xl flex items-center space-x-3">
                                <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/30 text-indigo-500">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Krisp</span>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 truncate block">
                                        {{ isset($project->phones['Krisp']) ? $project->phones['Krisp'] : 'Not added' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Zadarma -->
                            <div class="p-3 bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/60 rounded-xl flex items-center space-x-3">
                                <div class="p-2 rounded-lg bg-sky-50 dark:bg-sky-950/30 text-sky-500">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Zadarma</span>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 truncate block">
                                        {{ isset($project->phones['Zadarma']) ? $project->phones['Zadarma'] : 'Not added' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Contacts -->
                    <div class="md:col-span-2">
                        <span class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Contact Emails</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Corporate -->
                            <div class="p-3 bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/60 rounded-xl flex items-center space-x-3">
                                <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/30 text-indigo-500">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Corporate</span>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 truncate block" title="{{ isset($project->emails['Corporate']) ? $project->emails['Corporate'] : '' }}">
                                        {{ isset($project->emails['Corporate']) ? $project->emails['Corporate'] : 'Not added' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Private -->
                            <div class="p-3 bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/60 rounded-xl flex items-center space-x-3">
                                <div class="p-2 rounded-lg bg-sky-50 dark:bg-sky-950/30 text-sky-500">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Private</span>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 truncate block" title="{{ isset($project->emails['Private']) ? $project->emails['Private'] : '' }}">
                                        {{ isset($project->emails['Private']) ? $project->emails['Private'] : 'Not added' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    @if(config('features.notes_tab', true))
                    <div class="md:col-span-2">
                        <span class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Notes / Comments</span>
                        <div class="p-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800/80 rounded-xl text-sm text-slate-700 dark:text-slate-300 min-h-[100px] whitespace-pre-line">
                            {{ $project->notes ?: 'No notes available.' }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Director Card -->
        <div>
            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-6">
                <h2 class="font-outfit font-bold text-lg text-slate-700 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800/40 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Director Details
                </h2>

                <div class="space-y-5">
                    <!-- Director Name -->
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Director Full Name</span>
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                            {{ $project->director?->name ?: 'Not specified' }}
                        </span>
                    </div>

                    <!-- Fee Paid Status -->
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Fee Paid Status</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider mt-0.5
                            @if(($project->director?->fee_paid_status ?? '') === 'paid') bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400
                            @elseif(($project->director?->fee_paid_status ?? '') === 'pending') bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400
                            @else bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 @endif">
                            {{ $project->director?->fee_paid_status ?: 'unpaid' }}
                        </span>
                    </div>

                    <!-- Director Curator -->
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Curator / Manager</span>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300 flex items-center">
                            <span class="w-2 h-2 rounded-full mr-2 bg-sky-500"></span>
                            {{ $project->director?->manager?->name ?: 'Not assigned' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Container for CRM Modules -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
        <!-- Tabs Header -->
        <div class="flex border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-950/20 overflow-x-auto">
            <!-- Credentials Tab -->
            @if(config('features.credential_vault', true))
            <button @click="activeTab = 'credentials'" 
                    :class="activeTab === 'credentials' ? 'bg-sky-100/70 text-sky-700 border-sky-500 dark:bg-sky-950/40 dark:text-sky-400 dark:border-sky-400' : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
                    class="flex items-center px-6 py-4 border-b-2 font-semibold text-sm transition-all duration-150 whitespace-nowrap">
                <svg class="w-4.5 h-4.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Credentials
            </button>
            @endif

            <!-- Websites Tab -->
            @if(config('features.websites_tab', true))
            <button @click="activeTab = 'websites'" 
                    :class="activeTab === 'websites' ? 'bg-sky-100/70 text-sky-700 border-sky-500 dark:bg-sky-950/40 dark:text-sky-400 dark:border-sky-400' : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
                    class="flex items-center px-6 py-4 border-b-2 font-semibold text-sm transition-all duration-150 whitespace-nowrap">
                <svg class="w-4.5 h-4.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                </svg>
                Websites
            </button>
            @endif

            <!-- Boarding Tab -->
            @if(config('features.compliance_tab', true))
            <button @click="activeTab = 'boarding'" 
                    :class="activeTab === 'boarding' ? 'bg-sky-100/70 text-sky-700 border-sky-500 dark:bg-sky-950/40 dark:text-sky-400 dark:border-sky-400' : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
                    class="flex items-center px-6 py-4 border-b-2 font-semibold text-sm transition-all duration-150 whitespace-nowrap">
                <svg class="w-4.5 h-4.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Compliance (KYB/KYC)
            </button>
            @endif

            <!-- Reports Tab -->
            @if(config('features.reports_tab', true))
            <button @click="activeTab = 'reports'" 
                    :class="activeTab === 'reports' ? 'bg-sky-100/70 text-sky-700 border-sky-500 dark:bg-sky-950/40 dark:text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
                    class="flex items-center px-6 py-4 border-b-2 font-semibold text-sm transition-all duration-150 whitespace-nowrap">
                <svg class="w-4.5 h-4.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Reports & Deadlines
            </button>
            @endif

            <!-- Operations Tab -->
            @if(config('features.operations_tab', true))
            <button @click="activeTab = 'operations'" 
                    :class="activeTab === 'operations' ? 'bg-sky-100/70 text-sky-700 border-sky-500 dark:bg-sky-950/40 dark:text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
                    class="flex items-center px-6 py-4 border-b-2 font-semibold text-sm transition-all duration-150 whitespace-nowrap">
                @if(config('features.smm', true))
                <svg class="w-4.5 h-4.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                @else
                <svg class="w-4.5 h-4.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.907c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
                @endif
                {{ config('features.smm', true) ? 'SMM Tracker' : 'Reviews Manager' }}
            </button>
            @endif

            <!-- Comments Tab -->
            @if(config('features.company_comments', true))
            <button @click="activeTab = 'comments'" 
                    :class="activeTab === 'comments' ? 'bg-sky-100/70 text-sky-700 border-sky-500 dark:bg-sky-950/40 dark:text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
                    class="flex items-center px-6 py-4 border-b-2 font-semibold text-sm transition-all duration-150 whitespace-nowrap">
                <svg class="w-4.5 h-4.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                Comments
            </button>
            @endif

            <!-- Notes Tab -->
            @if(config('features.notes_tab', true))
            <button @click="activeTab = 'notes'" 
                    :class="activeTab === 'notes' ? 'bg-sky-100/70 text-sky-700 border-sky-500 dark:bg-sky-950/40 dark:text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
                    class="flex items-center px-6 py-4 border-b-2 font-semibold text-sm transition-all duration-150 whitespace-nowrap">
                <svg class="w-4.5 h-4.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Notes
            </button>
            @endif

            <!-- Changelog Tab -->
            @if(config('features.company_changelog', true))
            <button @click="activeTab = 'changelog'" 
                    :class="activeTab === 'changelog' ? 'bg-sky-100/70 text-sky-700 border-sky-500 dark:bg-sky-950/40 dark:text-sky-400 dark:border-sky-400' : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
                    class="flex items-center px-6 py-4 border-b-2 font-semibold text-sm transition-all duration-150 whitespace-nowrap">
                <svg class="w-4.5 h-4.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Changelog
            </button>
            @endif
        </div>

        <!-- Tab Body Content -->
        <div class="p-6">
            <!-- Credentials Tab Pane -->
            @if(config('features.credential_vault', true))
            <div x-show="activeTab === 'credentials'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                <livewire:projects.credentials-section :project="$project" />
            </div>
            @endif

            <!-- Websites Tab Pane -->
            @if(config('features.websites_tab', true))
            <div x-show="activeTab === 'websites'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                <livewire:projects.websites-section :project="$project" />
            </div>
            @endif

            <!-- Boarding Tab Pane -->
            @if(config('features.compliance_tab', true))
            <div x-show="activeTab === 'boarding'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                <livewire:projects.boarding-section :project="$project" />
            </div>
            @endif

            <!-- Reports Tab Pane -->
            @if(config('features.reports_tab', true))
            <div x-show="activeTab === 'reports'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                <livewire:projects.reports-section :project="$project" />
            </div>
            @endif

            <!-- Notes Tab Pane -->
            @if(config('features.notes_tab', true))
            <div x-show="activeTab === 'notes'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                <livewire:projects.notes-section :project="$project" />
            </div>
            @endif

            <!-- Changelog Tab Pane -->
            @if(config('features.company_changelog', true))
            <div x-show="activeTab === 'changelog'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                <livewire:projects.changelog-section :project="$project" />
            </div>
            @endif

            <!-- Operations Tab Pane -->
            @if(config('features.operations_tab', true))
            <div x-show="activeTab === 'operations'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                <livewire:projects.operations-section :project="$project" />
            </div>
            @endif

            <!-- Comments Tab Pane -->
            @if(config('features.company_comments', true))
            <div x-show="activeTab === 'comments'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                <livewire:projects.comments-section :project="$project" />
            </div>
            @endif
        </div>
    </div>

    <!-- Add Note Modal (Glassmorphic) -->
    @if($showNoteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay background -->
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="$set('showNoteModal', false)"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal Panel -->
                <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-100 dark:border-slate-800/80">
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/20">
                        <h3 class="text-base font-bold text-slate-850 dark:text-white font-outfit" id="modal-title">
                            Add Note to {{ $project->name }}
                        </h3>
                        <button type="button" wire:click="$set('showNoteModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors focus:outline-none cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <form wire:submit.prevent="saveNote">
                        <div class="px-6 py-6 space-y-4">
                            <!-- Note Content -->
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Note Content <span class="text-rose-500">*</span></label>
                                <textarea wire:model="noteContent" rows="4" placeholder="Write a note visible only to the team..." class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all resize-none"></textarea>
                                @error('noteContent') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/20 flex items-center justify-end space-x-2">
                            <button type="button" wire:click="$set('showNoteModal', false)" class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl transition-all duration-150 cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-semibold rounded-xl transition-colors shadow-sm cursor-pointer">
                                Add Note
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
