<x-app-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    <div class="space-y-8 animate-fade-in">
        <!-- Welcoming Section -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-sky-100/90 via-indigo-50 to-purple-100/90 dark:from-sky-950/30 dark:via-indigo-950/20 dark:to-purple-950/30 border border-sky-200/40 dark:border-sky-800/10 p-8 shadow-sm">
            <div class="absolute inset-0 bg-grid-slate-900/[0.04] dark:bg-grid-white/[0.04] bg-[size:20px_20px]"></div>
            <div class="relative z-10 space-y-2">
                <h1 class="font-outfit font-extrabold text-2xl md:text-3xl text-slate-800 dark:text-white tracking-tight">Welcome to Project Manager Hub!</h1>
                <p class="text-slate-600 dark:text-slate-400 max-w-xl text-sm leading-relaxed">
                    Unified dashboard for your IT projects. Monitor onboarding status, track report deadlines, and manage your team tasks.
                </p>
            </div>
            <!-- Decorative circle -->
            <div class="absolute -right-10 -bottom-10 h-40 w-40 rounded-full bg-sky-500/10 dark:bg-sky-400/5 blur-xl"></div>
        </div>

        <!-- Dashboard Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Companies -->
            <div class="relative group overflow-hidden bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-6 rounded-2xl transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-sky-500/5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Total Companies</span>
                    <span class="p-2.5 rounded-xl bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-400 transition-colors duration-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="font-outfit font-bold text-2xl md:text-3xl text-slate-700 dark:text-slate-100">{{ \App\Models\Project::count() }}</h3>
                    <p class="text-xs text-slate-400 mt-1">Registered in system</p>
                </div>
            </div>

            <!-- Onboarding Status -->
            <div class="relative group overflow-hidden bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-6 rounded-2xl transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-amber-500/5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">In Onboarding Process</span>
                    <span class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="font-outfit font-bold text-2xl md:text-3xl text-slate-700 dark:text-slate-100">{{ \App\Models\Project::where('status', 'onboarding')->count() }}</h3>
                    <p class="text-xs text-slate-400 mt-1">Awaiting verification</p>
                </div>
            </div>

            <!-- Active Status -->
            <div class="relative group overflow-hidden bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-6 rounded-2xl transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-emerald-500/5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Active Projects</span>
                    <span class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="font-outfit font-bold text-2xl md:text-3xl text-slate-700 dark:text-slate-100">{{ \App\Models\Project::where('status', 'active')->count() }}</h3>
                    <p class="text-xs text-slate-400 mt-1">Successfully integrated</p>
                </div>
            </div>

            <!-- Suspended Status -->
            <div class="relative group overflow-hidden bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-6 rounded-2xl transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-rose-500/5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Suspended</span>
                    <span class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="font-outfit font-bold text-2xl md:text-3xl text-slate-700 dark:text-slate-100">{{ \App\Models\Project::where('status', 'suspended')->count() }}</h3>
                    <p class="text-xs text-slate-400 mt-1">Require attention</p>
                </div>
            </div>
        </div>

        <!-- Bottom Grid Section (Recently Modified Companies + Recent Activity) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left (8 columns): Recently Modified Companies -->
            <div class="{{ config('features.activity_center', true) ? 'lg:col-span-8' : 'lg:col-span-12' }} bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-5 border-b border-slate-100 dark:border-slate-800/60">
                        <div>
                            <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Recently Modified Companies</h2>
                            <p class="text-xs text-slate-400 mt-1">List of recently added or updated companies in the system</p>
                        </div>
                        <a href="{{ route('projects.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 hover:underline">
                            View all
                        </a>
                    </div>

                    <div class="overflow-x-auto mt-4">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-slate-400 dark:text-slate-500 text-xs font-semibold uppercase border-b border-slate-100 dark:border-slate-800/60">
                                    <th class="py-3.5 px-4">Company</th>
                                    <th class="py-3.5 px-4">Website</th>
                                    <th class="py-3.5 px-4">UBO (Beneficiary)</th>
                                    <th class="py-3.5 px-4">Status</th>
                                    <th class="py-3.5 px-4">Director</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-sm">
                                @forelse(\App\Models\Project::latest()->take(5)->get() as $project)
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors duration-150">
                                        <td class="py-4 px-4">
                                            <a href="{{ route('projects.show', $project->id) }}" class="font-semibold text-slate-800 dark:text-slate-200 hover:text-sky-600 dark:hover:text-sky-400 transition-colors duration-150" wire:navigate>
                                                {{ $project->name }}
                                            </a>
                                        </td>
                                        <td class="py-4 px-4 text-slate-500 dark:text-slate-400">
                                            @if($project->website)
                                                <a href="{{ $project->website }}" target="_blank" class="hover:underline flex items-center space-x-1">
                                                    <span>{{ parse_url($project->website, PHP_URL_HOST) ?: $project->website }}</span>
                                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-4 px-4 text-slate-600 dark:text-slate-400">{{ $project->ubo ?: '-' }}</td>
                                        <td class="py-4 px-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium uppercase tracking-wider
                                                @if($project->status === 'active') bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400
                                                @elseif($project->status === 'onboarding') bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400
                                                @else bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 @endif">
                                                {{ $project->status }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-slate-600 dark:text-slate-400">
                                            {{ $project->director?->name ?: '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-slate-400">
                                            No companies in the system.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if(config('features.activity_center', true))
            <!-- Right (4 columns): Recent Activity Feed -->
            <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm flex flex-col">
                <div class="pb-5 border-b border-slate-100 dark:border-slate-800/60">
                    <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Recent Activity</h2>
                    <p class="text-xs text-slate-400 mt-1">Updates and tasks progress across the board</p>
                </div>

                <div class="mt-5 flex-1 overflow-y-auto max-h-[350px] pr-1 space-y-4">
                    @forelse(\App\Models\ActivityLog::with(['user', 'client', 'task', 'project'])->latest()->take(8)->get() as $log)
                        <div class="flex items-start space-x-3 text-sm">
                            <!-- Initials or icon -->
                            <div class="flex-shrink-0">
                                @if($log->user)
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-tr from-sky-400 to-indigo-500 text-white font-bold flex items-center justify-center text-xs uppercase shadow-sm">
                                        {{ substr($log->user->name, 0, 2) }}
                                    </div>
                                @elseif($log->client)
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-tr from-emerald-400 to-teal-500 text-white font-bold flex items-center justify-center text-xs uppercase shadow-sm" title="Client Portal">
                                        CP
                                    </div>
                                @else
                                    <div class="h-8 w-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center text-xs shadow-sm">
                                        <i class="fa-solid fa-robot"></i>
                                    </div>
                                @endif
                            </div>

                            <!-- Description and metadata -->
                            <div class="flex-1 min-w-0">
                                <p class="text-slate-600 dark:text-slate-300 text-xs leading-normal">
                                    {!! preg_replace(
                                        "/([Tt]ask) '(.*?)'/", 
                                        $log->task_id ? "$1 '<a href=\"" . route('tasks.kanban', ['task_id' => $log->task_id]) . "\" wire:navigate class=\"font-semibold text-indigo-600 dark:text-indigo-400 hover:underline\">$2</a>'" : "$1 '$2'",
                                        e($log->description)
                                    ) !!}
                                </p>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500">
                                        {{ $log->created_at->diffForHumans() }}
                                    </span>
                                    @if($log->client)
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30">
                                            Portal
                                        </span>
                                    @endif
                                    @if($log->project)
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 truncate max-w-[120px]" title="Company: {{ $log->project->name }}">
                                            • {{ $log->project->name }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-slate-400 dark:text-slate-500">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <i class="fa-solid fa-clock-rotate-left text-2xl text-slate-300 dark:text-slate-700"></i>
                                <span class="text-xs">No recent activity logged.</span>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
