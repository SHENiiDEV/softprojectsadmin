<div class="w-full" x-data="{
    tab: @entangle('activeTab'),
    init() {
        // Restore draft from localStorage
        const draft = localStorage.getItem('portal_draft_description');
        if (draft && draft.length > 0) {
            $wire.set('description', draft);
        }
    }
}">

    <!-- Client Welcome Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
        <div class="flex items-center space-x-3">
            <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-sky-400 to-indigo-500 text-white font-bold flex items-center justify-center text-sm uppercase shadow-md">
                {{ substr($client->name, 0, 2) }}
            </div>
            <div>
                <h1 class="font-outfit font-bold text-lg text-slate-900 dark:text-white leading-tight">Welcome, {{ $client->name }}</h1>
                <p class="text-xs text-slate-400 dark:text-slate-500">Manage your support requests and track progress</p>
            </div>
        </div>

        <!-- Tab Navigation -->
        <nav class="flex items-center bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-white/10 rounded-xl p-1 shadow-sm">
            <button @click="tab = 'dashboard'; $wire.set('activeTab', 'dashboard')"
                    :class="tab === 'dashboard' ? 'bg-gradient-to-r from-sky-500 to-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5'"
                    class="flex items-center space-x-2 px-4 py-2 rounded-lg text-xs font-semibold transition-all duration-200 cursor-pointer">
                <i class="fa-solid fa-chart-pie text-[11px]"></i>
                <span class="hidden sm:inline">Dashboard</span>
            </button>
            <button @click="tab = 'new-request'; $wire.set('activeTab', 'new-request')"
                    :class="tab === 'new-request' ? 'bg-gradient-to-r from-sky-500 to-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5'"
                    class="flex items-center space-x-2 px-4 py-2 rounded-lg text-xs font-semibold transition-all duration-200 cursor-pointer">
                <i class="fa-solid fa-plus-circle text-[11px]"></i>
                <span class="hidden sm:inline">New Request</span>
            </button>
            <button @click="tab = 'tickets'; $wire.set('activeTab', 'tickets')"
                    :class="tab === 'tickets' ? 'bg-gradient-to-r from-sky-500 to-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5'"
                    class="flex items-center space-x-2 px-4 py-2 rounded-lg text-xs font-semibold transition-all duration-200 cursor-pointer">
                <i class="fa-solid fa-ticket text-[11px]"></i>
                <span class="hidden sm:inline">My Tickets</span>
                @if($stats['open'] > 0)
                    <span class="bg-rose-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center leading-none">{{ $stats['open'] }}</span>
                @endif
            </button>
        </nav>
    </div>

    {{-- ============================================ --}}
    {{-- DASHBOARD TAB --}}
    {{-- ============================================ --}}
    <div x-show="tab === 'dashboard'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total -->
            <div class="glass-panel rounded-2xl p-5 hover:-translate-y-0.5 transition-all duration-200 animate-fade-in-up">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total</span>
                    <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/40">
                        <i class="fa-solid fa-layer-group text-indigo-500 text-sm"></i>
                    </div>
                </div>
                <p class="font-outfit font-bold text-3xl text-slate-900 dark:text-white">{{ $stats['total'] }}</p>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">All-time requests</p>
            </div>
            <!-- Open -->
            <div class="glass-panel rounded-2xl p-5 hover:-translate-y-0.5 transition-all duration-200 animate-fade-in-up animate-fade-in-up-delay-1">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Open</span>
                    <div class="p-2 rounded-lg bg-sky-50 dark:bg-sky-950/40">
                        <i class="fa-solid fa-clock text-sky-500 text-sm"></i>
                    </div>
                </div>
                <p class="font-outfit font-bold text-3xl text-slate-900 dark:text-white">{{ $stats['open'] }}</p>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Awaiting resolution</p>
            </div>
            <!-- In Review -->
            <div class="glass-panel rounded-2xl p-5 hover:-translate-y-0.5 transition-all duration-200 animate-fade-in-up animate-fade-in-up-delay-2">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Review</span>
                    <div class="p-2 rounded-lg bg-amber-50 dark:bg-amber-950/40">
                        <i class="fa-solid fa-magnifying-glass text-amber-500 text-sm"></i>
                    </div>
                </div>
                <p class="font-outfit font-bold text-3xl text-slate-900 dark:text-white">{{ $stats['in_review'] }}</p>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Being verified</p>
            </div>
            <!-- Resolved -->
            <div class="glass-panel rounded-2xl p-5 hover:-translate-y-0.5 transition-all duration-200 animate-fade-in-up animate-fade-in-up-delay-3">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Resolved</span>
                    <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/40">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                    </div>
                </div>
                <p class="font-outfit font-bold text-3xl text-slate-900 dark:text-white">{{ $stats['resolved'] }}</p>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Completed</p>
            </div>
        </div>

        <!-- Recent Tickets + Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Recent Tickets -->
            <div class="lg:col-span-2 glass-panel rounded-2xl p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-outfit font-bold text-base text-slate-900 dark:text-white">Recent Tickets</h2>
                    <button @click="tab = 'tickets'; $wire.set('activeTab', 'tickets')" class="text-xs font-semibold text-indigo-500 hover:text-indigo-400 transition-colors cursor-pointer">
                        View All <i class="fa-solid fa-arrow-right ml-1 text-[10px]"></i>
                    </button>
                </div>
                <div class="space-y-2">
                    @forelse($recentTasks as $t)
                        <div wire:click="openTaskModal({{ $t->id }})"
                             class="flex items-center justify-between p-3 bg-slate-50 dark:bg-white/3 border border-slate-100 dark:border-white/5 rounded-xl cursor-pointer hover:bg-slate-100 dark:hover:bg-white/5 transition-all duration-150 group">
                            <div class="flex items-center space-x-3 overflow-hidden">
                                <div class="w-1 h-8 rounded-full flex-shrink-0
                                    {{ $t->priority === 'critical' ? 'bg-rose-500' : ($t->priority === 'high' ? 'bg-orange-500' : ($t->priority === 'medium' ? 'bg-sky-500' : 'bg-emerald-500')) }}">
                                </div>
                                <div class="overflow-hidden">
                                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ $t->title }}</p>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ $t->project ? $t->project->name : 'General' }} · {{ $t->updated_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="flex-shrink-0 ml-2">
                                @if($t->status === 'todo')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400">To Do</span>
                                @elseif($t->status === 'in_progress')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-sky-100 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400">In Progress</span>
                                @elseif($t->status === 'review')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400">Review</span>
                                @elseif($t->status === 'done')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400">Done</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-slate-400 dark:text-slate-500">
                            <i class="fa-regular fa-folder-open text-2xl mb-2 block"></i>
                            <p class="text-xs">No tickets yet. Create your first request!</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="space-y-4">
                <div class="glass-panel rounded-2xl p-6 space-y-4">
                    <h2 class="font-outfit font-bold text-base text-slate-900 dark:text-white">Quick Actions</h2>
                    <button @click="tab = 'new-request'; $wire.set('activeTab', 'new-request')"
                            class="w-full flex items-center space-x-3 p-3 bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 text-white rounded-xl transition-all duration-200 hover:-translate-y-0.5 shadow-md shadow-indigo-500/20 cursor-pointer">
                        <div class="p-2 bg-white/20 rounded-lg">
                            <i class="fa-solid fa-plus text-sm"></i>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-bold">New Request</p>
                            <p class="text-[10px] text-white/70">Submit a support ticket</p>
                        </div>
                    </button>
                    <button @click="tab = 'tickets'; $wire.set('activeTab', 'tickets'); $wire.set('statusFilter', 'open')"
                            class="w-full flex items-center space-x-3 p-3 bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/5 hover:bg-slate-100 dark:hover:bg-white/10 rounded-xl transition-all duration-200 cursor-pointer">
                        <div class="p-2 bg-sky-50 dark:bg-sky-950/40 rounded-lg">
                            <i class="fa-solid fa-clock text-sky-500 text-sm"></i>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Open Tickets</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ $stats['open'] }} awaiting resolution</p>
                        </div>
                    </button>
                </div>

                <!-- Support Info -->
                <div class="glass-panel rounded-2xl p-6 space-y-3">
                    <h3 class="font-outfit font-bold text-sm text-slate-900 dark:text-white">Need Help?</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Our support team typically responds within 2-4 business hours. For urgent issues, mark your request as <span class="font-bold text-rose-500">Critical</span>.</p>
                    <div class="flex items-center space-x-2 text-[10px] text-slate-400 dark:text-slate-500">
                        <i class="fa-solid fa-shield-check text-emerald-500"></i>
                        <span>All communications are encrypted</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- NEW REQUEST TAB --}}
    {{-- ============================================ --}}
    <div x-show="tab === 'new-request'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="max-w-2xl mx-auto">
            @if($submitted)
                <!-- Success State -->
                <div class="glass-panel rounded-2xl p-8 text-center space-y-6 animate-fade-in-up">
                    <div class="mx-auto w-16 h-16 bg-emerald-100 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 rounded-2xl flex items-center justify-center">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-3xl"></i>
                    </div>
                    <div class="space-y-2">
                        <h2 class="text-xl font-bold font-outfit text-slate-900 dark:text-white tracking-tight">Request Created Successfully!</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Your ticket has been submitted. Our team will review it shortly.</p>
                    </div>

                    @if($createdTaskTitle)
                        <div class="bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/5 rounded-xl p-4 text-left">
                            <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Ticket Created</span>
                            <p class="text-slate-800 dark:text-slate-200 text-sm font-semibold truncate mt-1">{{ $createdTaskTitle }}</p>
                        </div>
                    @endif

                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <button wire:click="goToTickets"
                                class="flex-1 py-3 bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 text-white font-semibold rounded-xl text-sm transition-all duration-200 hover:-translate-y-0.5 cursor-pointer shadow-lg shadow-indigo-500/20">
                            <i class="fa-solid fa-ticket mr-1.5"></i> View My Tickets
                        </button>
                        <button wire:click="resetFormState"
                                class="flex-1 py-3 bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-300 font-semibold rounded-xl text-sm transition-all duration-200 hover:bg-slate-200 dark:hover:bg-white/10 cursor-pointer">
                            <i class="fa-solid fa-plus mr-1.5"></i> New Request
                        </button>
                    </div>
                </div>
            @else
                <!-- Request Form -->
                <div class="glass-panel rounded-2xl p-6 sm:p-8 space-y-6 animate-fade-in-up">
                    <!-- Form Header -->
                    <div>
                        <div class="flex items-center space-x-2 mb-3">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-indigo-500 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-500/20 px-3 py-1 rounded-full">
                                {{ $client->name }}
                            </span>
                        </div>
                        <h1 class="text-xl font-bold font-outfit text-slate-900 dark:text-white tracking-tight">Create Support Request</h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Fill in the details below and our team will get back to you.</p>
                    </div>

                    <!-- Progress Steps -->
                    <div class="flex items-center space-x-2 text-[10px] font-semibold">
                        <div class="flex items-center space-x-1.5 {{ $selectedCompanyId ? 'text-emerald-500' : 'text-indigo-500' }}">
                            <span class="w-5 h-5 rounded-full {{ $selectedCompanyId ? 'bg-emerald-100 dark:bg-emerald-950/40' : 'bg-indigo-100 dark:bg-indigo-950/40' }} flex items-center justify-center">
                                @if($selectedCompanyId) <i class="fa-solid fa-check text-[8px]"></i> @else 1 @endif
                            </span>
                            <span>Project</span>
                        </div>
                        <div class="w-6 h-px bg-slate-200 dark:bg-white/10"></div>
                        <div class="flex items-center space-x-1.5 {{ $description ? 'text-emerald-500' : ($selectedCompanyId ? 'text-indigo-500' : 'text-slate-400') }}">
                            <span class="w-5 h-5 rounded-full {{ $description ? 'bg-emerald-100 dark:bg-emerald-950/40' : ($selectedCompanyId ? 'bg-indigo-100 dark:bg-indigo-950/40' : 'bg-slate-100 dark:bg-slate-800') }} flex items-center justify-center">
                                @if($description) <i class="fa-solid fa-check text-[8px]"></i> @else 2 @endif
                            </span>
                            <span>Details</span>
                        </div>
                        <div class="w-6 h-px bg-slate-200 dark:bg-white/10"></div>
                        <div class="flex items-center space-x-1.5 text-slate-400">
                            <span class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">3</span>
                            <span>Submit</span>
                        </div>
                    </div>

                    <form wire:submit.prevent="submitReport" class="space-y-5">
                        <!-- Company Select -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Company / Project <span class="text-rose-500">*</span></label>
                            <select wire:model.live="selectedCompanyId"
                                    class="w-full px-4 py-3 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-white/10 rounded-xl text-sm text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all duration-200">
                                <option value="">Select Company...</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedCompanyId') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Website Select -->
                        @if($selectedCompanyId)
                            <div class="animate-fade-in space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Website <span class="text-rose-500">*</span></label>
                                    <select wire:model.live="selectedWebsiteId"
                                            class="w-full px-4 py-3 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-white/10 rounded-xl text-sm text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all duration-200">
                                        <option value="">Select Website...</option>
                                        @foreach($websites as $web)
                                            <option value="{{ $web->id }}">{{ $web->name }} ({{ parse_url($web->url, PHP_URL_HOST) ?: $web->url }})</option>
                                        @endforeach
                                    </select>
                                    @error('selectedWebsiteId') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                @if($selectedWebsite)
                                    <div class="bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/5 rounded-xl p-3 flex items-center justify-between animate-fade-in">
                                        <div class="flex items-center space-x-2">
                                            <i class="fa-solid fa-globe text-slate-400 text-sm"></i>
                                            <span class="text-xs text-slate-600 dark:text-slate-400 font-mono truncate">{{ $selectedWebsite->url }}</span>
                                        </div>
                                        @if($selectedWebsite->status === 'Live')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span> Live
                                            </span>
                                        @elseif($selectedWebsite->status === 'Test')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400">Test</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Request Type + Priority Row -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Request Type <span class="text-rose-500">*</span></label>
                                <select wire:model.live="requestType"
                                        class="w-full px-4 py-3 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-white/10 rounded-xl text-sm text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all duration-200">
                                    <option value="General Question">General Question</option>
                                    <option value="Traffic Launch">🚀 Traffic Launch</option>
                                    <option value="Design Changes">Design Changes</option>
                                    <option value="Integration Changes">Integration Changes</option>
                                    <option value="Bug Report">Bug Report</option>
                                    <option value="Other">Other</option>
                                </select>
                                @error('requestType') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Priority</label>
                                <select wire:model="urgency"
                                        class="w-full px-4 py-3 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-white/10 rounded-xl text-sm text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all duration-200">
                                    <option value="low">🟢 Low</option>
                                    <option value="medium">🔵 Medium</option>
                                    <option value="high">🟠 High</option>
                                    <option value="critical">🔴 Critical</option>
                                </select>
                                @error('urgency') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- TRAFFIC LAUNCH FORM FIELDS --}}
                        @if($requestType === 'Traffic Launch')
                            <div class="space-y-6 bg-slate-50/50 dark:bg-white/3 border border-slate-200/80 dark:border-white/10 rounded-2xl p-5 animate-fade-in">
                                <div class="flex items-center space-x-2 pb-3 border-b border-slate-200/60 dark:border-white/10">
                                    <span class="p-2 rounded-xl bg-sky-500/10 text-sky-500">
                                        <i class="fa-solid fa-rocket text-base"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-800 dark:text-white">Traffic Launch Campaign Details</h4>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Configure target metrics, GEO distribution, and traffic sources for the campaign.</p>
                                    </div>
                                </div>

                                <!-- Month & Plan -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Target Month <span class="text-rose-500">*</span></label>
                                        <select wire:model="trafficMonth" class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-950 border border-slate-200 dark:border-white/10 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                            <option value="">Select Month...</option>
                                            @foreach($this->getMonthOptions() as $mKey => $mLabel)
                                                <option value="{{ $mKey }}">{{ $mLabel }}</option>
                                            @endforeach
                                        </select>
                                        @error('trafficMonth') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Plan Name <span class="text-rose-500">*</span></label>
                                        <input type="text" wire:model="trafficPlan" placeholder="e.g. Main Traffic Launch Plan" class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-950 border border-slate-200 dark:border-white/10 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                        @error('trafficPlan') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- GEO Distribution Header & Total Counter -->
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            GEO Distribution (Must equal 100%) <span class="text-rose-500">*</span>
                                        </label>
                                        <div class="flex items-center space-x-2">
                                            @php $geoTotal = $this->geoTotalPercent; @endphp
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $geoTotal === 100 ? 'bg-emerald-100 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400' }}">
                                                Total: {{ $geoTotal }}% / 100%
                                            </span>
                                            <button type="button" wire:click="addGeoRow" class="px-2.5 py-1 bg-sky-500/10 hover:bg-sky-500/20 text-sky-600 dark:text-sky-400 rounded-lg text-xs font-semibold transition-all">
                                                <i class="fa-solid fa-plus text-[10px] mr-1"></i> Add Country
                                            </button>
                                        </div>
                                    </div>

                                    <!-- GEO Rows -->
                                    <div class="space-y-2">
                                        @foreach($trafficGeo as $index => $geo)
                                            <div class="flex items-center gap-2">
                                                <!-- Country Select (Searchable & Grouped by Region) -->
                                                <div x-data="{
                                                         ts: null,
                                                         init() {
                                                             this.$nextTick(() => {
                                                                 const el = this.$refs.countrySelect;
                                                                 if (!el) return;
                                                                 if (el.tomselect) { el.tomselect.destroy(); }
                                                                 this.ts = new TomSelect(el, {
                                                                     placeholder: 'Search country or region...',
                                                                     allowEmptyOption: true,
                                                                     maxOptions: null,
                                                                     onChange: (val) => {
                                                                         @this.set('trafficGeo.{{ $index }}.code', val);
                                                                     }
                                                                 });
                                                             });
                                                         }
                                                      }"
                                                      wire:key="geo-country-select-{{ $index }}"
                                                      class="flex-1">
                                                     <select x-ref="countrySelect" wire:model="trafficGeo.{{ $index }}.code" class="w-full">
                                                         <option value="">Search country or region...</option>
                                                         @foreach($this->getGroupedCountries() as $regionName => $regionCountries)
                                                             <optgroup label="{{ $regionName }}">
                                                                 @foreach($regionCountries as $cCode => $cName)
                                                                     <option value="{{ $cCode }}">{{ $cName }}</option>
                                                                 @endforeach
                                                             </optgroup>
                                                         @endforeach
                                                     </select>
                                                 </div>

                                                <!-- Percentage Input -->
                                                <div class="w-28 relative">
                                                    <input type="number" wire:model.live="trafficGeo.{{ $index }}.percent" min="1" max="100" placeholder="70" class="w-full pl-3 pr-7 py-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-white/10 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                                                </div>

                                                <!-- Remove Button -->
                                                @if(count($trafficGeo) > 1)
                                                    <button type="button" wire:click="removeGeoRow({{ $index }})" class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors cursor-pointer">
                                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('trafficGeo') <span class="text-xs text-rose-500 block">{{ $message }}</span> @enderror
                                </div>

                                <!-- Bounce Rate, Pages, Time -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Bounce Rate <span class="text-rose-500">*</span></label>
                                        <input type="text" wire:model="trafficBounceRate" placeholder="e.g. 25-30%" class="w-full px-3.5 py-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-white/10 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                        @error('trafficBounceRate') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Pages <span class="text-rose-500">*</span></label>
                                        <input type="text" wire:model="trafficPages" placeholder="e.g. 3-5 pages" class="w-full px-3.5 py-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-white/10 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                        @error('trafficPages') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Time on Page (sec) <span class="text-rose-500">*</span></label>
                                        <input type="number" min="1" wire:model="trafficTime" placeholder="e.g. 15" class="w-full px-3.5 py-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-white/10 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                        @error('trafficTime') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- Channels Breakdown -->
                                <div class="space-y-4 pt-2 border-t border-slate-200/60 dark:border-white/10">
                                    <h5 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">Traffic Channels Breakdown</h5>

                                    <!-- Referral Traffic -->
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div class="sm:col-span-1">
                                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Referral Traffic (%)</label>
                                            <div class="relative">
                                                <input type="number" wire:model="trafficReferralPercent" min="0" max="100" placeholder="15" class="w-full pl-3 pr-7 py-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-white/10 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                                            </div>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Referral Links (One per line)</label>
                                            <textarea wire:model="trafficReferralLinks" rows="2" placeholder="https://example1.com&#10;https://example2.com" class="w-full px-3 py-1.5 bg-white dark:bg-slate-950 border border-slate-200 dark:border-white/10 rounded-xl text-xs font-mono text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 resize-none"></textarea>
                                        </div>
                                    </div>

                                    <!-- Social Traffic Block (Total + Facebook/Instagram Breakdown) -->
                                    <div class="p-3 bg-white/60 dark:bg-slate-900/60 border border-slate-200/60 dark:border-white/10 rounded-xl space-y-3">
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Social Traffic Total (%)</label>
                                                <div class="relative">
                                                    <input type="number" wire:model="trafficSocialPercent" min="0" max="100" placeholder="20" class="w-full pl-3 pr-7 py-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-white/10 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                                                </div>
                                            </div>

                                            <div class="sm:col-span-2 grid grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-xs font-semibold text-blue-600 dark:text-blue-400 mb-1 flex items-center gap-1">
                                                        <i class="fa-brands fa-facebook text-xs"></i> Facebook (%)
                                                    </label>
                                                    <div class="relative">
                                                        <input type="number" wire:model="trafficSocialFbPercent" min="0" max="100" placeholder="10" class="w-full pl-3 pr-7 py-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-white/10 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-semibold text-pink-600 dark:text-pink-400 mb-1 flex items-center gap-1">
                                                        <i class="fa-brands fa-instagram text-xs"></i> Instagram (%)
                                                    </label>
                                                    <div class="relative">
                                                        <input type="number" wire:model="trafficSocialInstPercent" min="0" max="100" placeholder="10" class="w-full pl-3 pr-7 py-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-white/10 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Organic & Direct Traffic -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Organic Traffic (%)</label>
                                            <div class="relative">
                                                <input type="number" wire:model="trafficOrganicPercent" min="0" max="100" placeholder="35" class="w-full pl-3 pr-7 py-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-white/10 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Direct Traffic (%)</label>
                                            <div class="relative">
                                                <input type="number" wire:model="trafficDirectPercent" min="0" max="100" placeholder="30" class="w-full pl-3 pr-7 py-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-white/10 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Comment -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Comment (Optional)</label>
                                    <textarea wire:model="trafficComment" rows="3" placeholder="Any additional notes or instructions for the campaign..." class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-950 border border-slate-200 dark:border-white/10 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 resize-none"></textarea>
                                </div>
                            </div>
                        @else
                            <!-- Description for Standard Request Types -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Description <span class="text-rose-500">*</span></label>
                                <textarea wire:model="description"
                                          x-on:input="localStorage.setItem('portal_draft_description', $event.target.value)"
                                          rows="5"
                                          placeholder="Describe your issue or request in detail. The more details you provide, the faster we can help..."
                                          class="w-full px-4 py-3 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-white/10 rounded-xl text-sm text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all duration-200 resize-none"></textarea>
                                @error('description') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        @if(config('features.task_attachments', true))
                            <!-- Attachments -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Attachments</label>
                                <div x-data="{ isDragging: false }"
                                     @dragover.prevent="isDragging = true"
                                     @dragleave.prevent="isDragging = false"
                                     @drop.prevent="isDragging = false; $wire.uploadMultiple('attachments', $event.dataTransfer.files)"
                                     :class="isDragging ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/20' : 'border-slate-200 dark:border-white/10'"
                                     class="relative border-2 border-dashed rounded-xl p-5 text-center hover:bg-slate-50 dark:hover:bg-white/3 transition-all duration-200 bg-white dark:bg-transparent cursor-pointer">
                                    <input type="file" wire:model="attachments" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                    <div class="space-y-1">
                                        <i class="fa-solid fa-cloud-arrow-up text-2xl text-slate-400"></i>
                                        <p class="text-xs font-semibold text-slate-600 dark:text-slate-400">Drop files here or click to browse</p>
                                        <p class="text-[10px] text-slate-400">Max 10MB per file</p>
                                    </div>
                                    <div wire:loading wire:target="attachments" class="text-xs text-indigo-500 mt-2 font-semibold">
                                        <i class="fa-solid fa-spinner animate-spin mr-1"></i> Uploading...
                                    </div>
                                </div>

                                @if(!empty($attachments))
                                    <div class="mt-2 space-y-1.5">
                                        @foreach($attachments as $index => $file)
                                            <div class="flex items-center justify-between p-2 bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/5 rounded-lg animate-fade-in">
                                                <div class="flex items-center space-x-2 overflow-hidden">
                                                    <i class="fa-solid fa-file text-slate-400 text-xs"></i>
                                                    <span class="text-xs font-medium text-slate-700 dark:text-slate-300 truncate">{{ $file->getClientOriginalName() }}</span>
                                                    <span class="text-[9px] text-slate-400 flex-shrink-0">({{ number_format($file->getSize() / 1024, 1) }} KB)</span>
                                                </div>
                                                <button type="button" wire:click="removeAttachment({{ $index }})" class="p-1 text-slate-400 hover:text-rose-500 transition-colors cursor-pointer">
                                                    <i class="fa-solid fa-xmark text-xs"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @error('attachments.*') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <!-- Submit -->
                        <button type="submit"
                                class="w-full py-3.5 bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 text-white font-bold rounded-xl text-sm transition-all duration-200 hover:-translate-y-0.5 cursor-pointer shadow-lg shadow-indigo-500/20"
                                x-on:click="localStorage.removeItem('portal_draft_description')">
                            <i class="fa-solid fa-paper-plane mr-1.5"></i> Submit Request
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- MY TICKETS TAB --}}
    {{-- ============================================ --}}
    <div x-show="tab === 'tickets'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4">

        <!-- Filters Bar -->
        <div class="glass-panel rounded-2xl p-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <!-- Search -->
                <div class="relative flex-1">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" wire:model.live.debounce.300ms="searchQuery"
                           placeholder="Search tickets..."
                           class="w-full pl-9 pr-4 py-2.5 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-white/10 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                </div>

                <!-- Status Filter Pills -->
                <div class="flex items-center space-x-1 overflow-x-auto">
                    @php
                        $filters = [
                            'all' => 'All',
                            'open' => 'Open',
                            'in_progress' => 'In Progress',
                            'review' => 'Review',
                            'done' => 'Done',
                        ];
                    @endphp
                    @foreach($filters as $value => $label)
                        <button wire:click="$set('statusFilter', '{{ $value }}')"
                                class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider whitespace-nowrap transition-all duration-150 cursor-pointer
                                {{ $statusFilter === $value
                                    ? 'bg-indigo-500 text-white shadow-sm'
                                    : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <!-- Sort Toggle -->
                <button wire:click="toggleSort" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5 transition-all cursor-pointer" title="Toggle sort order">
                    <i class="fa-solid {{ $sortDirection === 'desc' ? 'fa-arrow-down-wide-short' : 'fa-arrow-up-wide-short' }} text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Tickets List -->
        <div class="space-y-3">
            @forelse($tickets as $t)
                <div wire:click="openTaskModal({{ $t->id }})" wire:key="ticket-{{ $t->id }}"
                     class="glass-panel rounded-xl p-4 cursor-pointer hover:-translate-y-0.5 hover:shadow-lg transition-all duration-200 group priority-stripe-{{ $t->priority }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 overflow-hidden space-y-2">
                            <!-- Title Row -->
                            <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-white/5 px-2 py-0.5 rounded">#{{ $t->id }}</span>
                                <span class="text-[9px] font-bold uppercase tracking-wider text-indigo-500 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 px-2 py-0.5 rounded">{{ $t->project ? $t->project->name : 'General' }}</span>
                            </div>
                            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors truncate">{{ $t->title }}</h3>
                            @if($t->description)
                                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1">{{ Str::limit($t->description, 120) }}</p>
                            @endif

                            <!-- Meta Row -->
                            <div class="flex items-center space-x-3 text-[10px] text-slate-400 dark:text-slate-500">
                                <!-- Priority -->
                                <span class="flex items-center space-x-1">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $t->priority === 'critical' ? 'bg-rose-500' : ($t->priority === 'high' ? 'bg-orange-500' : ($t->priority === 'medium' ? 'bg-sky-500' : 'bg-emerald-500')) }}"></span>
                                    <span class="capitalize">{{ $t->priority }}</span>
                                </span>
                                @if(config('features.client_portal_comments', true))
                                    <span><i class="fa-regular fa-comment mr-0.5"></i> {{ $t->comments()->where('is_private', false)->count() + $t->allComments()->where('is_private', false)->whereNotNull('parent_id')->count() }}</span>
                                @endif
                                @if(config('features.task_attachments', true) && $t->hasMedia('documents'))
                                    <span><i class="fa-solid fa-paperclip mr-0.5"></i> {{ $t->getMedia('documents')->count() }}</span>
                                @endif
                                <span>{{ $t->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="flex-shrink-0">
                            @if($t->status === 'todo')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">To Do</span>
                            @elseif($t->status === 'in_progress')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-sky-100 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400">In Progress</span>
                            @elseif($t->status === 'review')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-100 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400">Review</span>
                            @elseif($t->status === 'done')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400">Done</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="glass-panel rounded-2xl p-12 text-center space-y-3">
                    <i class="fa-regular fa-folder-open text-3xl text-slate-300 dark:text-slate-600"></i>
                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">No tickets found</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ $searchQuery ? 'Try adjusting your search or filters' : 'Create your first support request' }}</p>
                    @if(!$searchQuery)
                        <button @click="tab = 'new-request'; $wire.set('activeTab', 'new-request')"
                                class="mt-2 px-4 py-2 bg-gradient-to-r from-sky-500 to-indigo-600 text-white text-xs font-bold rounded-xl hover:-translate-y-0.5 transition-all cursor-pointer shadow-md shadow-indigo-500/20">
                            <i class="fa-solid fa-plus mr-1"></i> New Request
                        </button>
                    @endif
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($tickets->hasPages())
            <div class="mt-4">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>

    {{-- ============================================ --}}
    {{-- TICKET DETAIL MODAL (Jira-like) --}}
    {{-- ============================================ --}}
    @if($showTaskModal && $viewTaskId)
        @php
            $viewTask = \App\Models\Task::with(['comments.replies', 'media'])->find($viewTaskId);
        @endphp
        @if($viewTask)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm animate-fade-in" @keydown.escape.window="$wire.closeModal()">
                <div class="bg-white dark:bg-slate-900 w-full max-w-5xl rounded-2xl max-h-[92vh] overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-700/80 flex flex-col" @click.away="$wire.closeModal()">

                    {{-- ── Modal Header ── --}}
                    <div class="flex items-start justify-between px-6 py-4 border-b border-slate-200/80 dark:border-white/5 flex-shrink-0">
                        <div class="space-y-1.5 pr-8 overflow-hidden min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap text-[10px]">
                                <span class="font-mono font-bold text-slate-400 dark:text-slate-500">TICKET-{{ $viewTask->id }}</span>
                                <span class="text-slate-300 dark:text-slate-600">/</span>
                                <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $viewTask->project ? $viewTask->project->name : 'General' }}</span>
                            </div>
                            <h2 class="text-xl font-bold font-outfit text-slate-900 dark:text-white leading-tight tracking-tight">{{ $viewTask->title }}</h2>
                        </div>
                        <button type="button" wire:click="closeModal" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5 transition-all cursor-pointer flex-shrink-0 mt-1">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    {{-- ── Modal Body: 2-Column ── --}}
                    <div class="flex-1 overflow-hidden flex flex-col lg:flex-row min-h-0">

                        {{-- LEFT PANEL: Description + Activity --}}
                        <div class="lg:w-[62%] flex flex-col min-h-0 border-r border-slate-100 dark:border-slate-700/40">
                            <div class="flex-1 overflow-y-auto custom-scroll px-6 py-5 space-y-6">

                                {{-- Description --}}
                                <div class="space-y-2">
                                    <h4 class="text-xs uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider flex items-center gap-1.5">
                                        <i class="fa-solid fa-align-left text-[10px]"></i> Description
                                    </h4>
                                    <div class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">{!! $viewTask->description ?: 'No description provided.' !!}</div>
                                </div>

                                {{-- Attachments --}}
                                @if(config('features.task_attachments', true) && $viewTask->hasMedia('documents'))
                                <div class="space-y-2">
                                    <h4 class="text-xs uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider flex items-center gap-1.5">
                                        <i class="fa-solid fa-paperclip text-[10px]"></i> Attachments
                                    </h4>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($viewTask->getMedia('documents') as $media)
                                            <a href="{{ Storage::url($media->id . '/' . $media->file_name) }}" target="_blank"
                                               class="inline-flex items-center gap-2 px-3 py-2 bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg hover:bg-sky-50 dark:hover:bg-sky-950/20 hover:border-sky-200 dark:hover:border-sky-800/40 transition-all text-xs group">
                                                <i class="fa-solid fa-file text-slate-400 group-hover:text-sky-500 transition-colors"></i>
                                                <span class="font-medium text-slate-700 dark:text-slate-300 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors truncate max-w-[140px]">{{ $media->file_name }}</span>
                                                <span class="text-[10px] text-slate-400">{{ number_format($media->size / 1024, 1) }}KB</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                                @endif

                                {{-- Activity / Discussion --}}
                                @if(config('features.client_portal_comments', true))
                                <div class="space-y-4 pt-2 border-t border-slate-100 dark:border-slate-700/40">
                                    <h4 class="text-xs uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider flex items-center gap-1.5">
                                        <i class="fa-solid fa-stream text-[10px]"></i> Activity
                                        <span class="ml-1 text-[9px] bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 px-1.5 py-0.5 rounded-full font-bold">
                                            {{ $viewTask->comments()->where('is_private', false)->count() + $viewTask->allComments()->where('is_private', false)->whereNotNull('parent_id')->count() }}
                                        </span>
                                    </h4>

                                    {{-- Comment List (Jira-style flat threads) --}}
                                    <div class="space-y-0">
                                        @forelse($viewTask->comments()->where('is_private', false)->get() as $comment)
                                            @php $isClient = !empty($comment->client_id); @endphp
                                            <div class="group relative flex gap-3 py-4 {{ !$loop->last ? 'border-b border-slate-100 dark:border-slate-700/30' : '' }}">
                                                {{-- Avatar --}}
                                                <div class="flex-shrink-0 mt-0.5">
                                                    @if($isClient)
                                                        <div class="h-8 w-8 rounded-full bg-gradient-to-br from-sky-400 to-cyan-500 text-white flex items-center justify-center text-[10px] font-bold uppercase shadow-sm">
                                                            {{ substr($comment->client->name, 0, 2) }}
                                                        </div>
                                                    @else
                                                        <div class="h-8 w-8 rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 text-white flex items-center justify-center text-[10px] font-bold uppercase shadow-sm">
                                                            {{ substr($comment->user ? $comment->user->name : 'S', 0, 2) }}
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- Comment Content --}}
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-baseline gap-2 mb-1">
                                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                            {{ $isClient ? $comment->client->name : ($comment->user ? $comment->user->name : 'System') }}
                                                        </span>
                                                        @if(!$isClient)
                                                            <span class="text-[9px] font-semibold bg-indigo-100 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 px-1.5 py-0.5 rounded">Team</span>
                                                        @endif
                                                        <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ $comment->created_at->diffForHumans() }}</span>
                                                    </div>
                                                    <div class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">{!! $comment->formatted_content !!}</div>

                                                    {{-- Nested Replies --}}
                                                    @if($comment->replies()->where('is_private', false)->count() > 0)
                                                        <div class="mt-3 ml-2 pl-4 border-l-2 border-slate-200 dark:border-slate-700/60 space-y-3">
                                                            @foreach($comment->replies()->where('is_private', false)->get() as $reply)
                                                                @php $isReplyClient = !empty($reply->client_id); @endphp
                                                                <div class="flex gap-2.5">
                                                                    <div class="flex-shrink-0 mt-0.5">
                                                                        <div class="h-6 w-6 rounded-full {{ $isReplyClient ? 'bg-gradient-to-br from-sky-400 to-cyan-500' : 'bg-gradient-to-br from-indigo-500 to-violet-500' }} text-white flex items-center justify-center text-[8px] font-bold uppercase">
                                                                            {{ substr($reply->user ? $reply->user->name : ($reply->client ? $reply->client->name : 'S'), 0, 2) }}
                                                                        </div>
                                                                    </div>
                                                                    <div class="flex-1 min-w-0">
                                                                        <div class="flex items-baseline gap-2 mb-0.5">
                                                                            <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                                                                {{ $reply->user ? $reply->user->name : ($reply->client ? $reply->client->name : 'System') }}
                                                                            </span>
                                                                            <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ $reply->created_at->diffForHumans() }}</span>
                                                                        </div>
                                                                        <div class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed whitespace-pre-wrap">{!! $reply->formatted_content !!}</div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    {{-- Reply button --}}
                                                    <div x-data="{ showReply: false }" class="mt-2">
                                                        <button type="button" @click="showReply = !showReply" class="text-[10px] font-semibold text-slate-400 hover:text-indigo-500 transition-colors cursor-pointer focus:outline-none flex items-center gap-1">
                                                            <i class="fa-solid fa-reply text-[9px]"></i> Reply
                                                        </button>
                                                        <div x-show="showReply" x-transition class="mt-2 space-y-2 animate-fade-in">
                                                            <textarea wire:model="replyCommentContent.{{ $comment->id }}" rows="2" placeholder="Write a reply..."
                                                                class="w-full px-3 py-2 bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all resize-none"></textarea>
                                                            <div class="flex justify-end gap-2">
                                                                <button type="button" @click="showReply = false" class="px-3 py-1.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 text-[10px] font-semibold cursor-pointer">Cancel</button>
                                                                <button type="button" wire:click="addReply({{ $comment->id }})" @click="showReply = false" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-[10px] transition-all cursor-pointer shadow-sm">
                                                                    Save
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="flex flex-col items-center py-10 text-slate-400 dark:text-slate-500">
                                                <i class="fa-regular fa-comment-dots text-3xl mb-2 opacity-40"></i>
                                                <p class="text-xs">No activity yet.</p>
                                            </div>
                                        @endforelse
                                    </div>

                                    {{-- Comment Input --}}
                                    <form wire:submit.prevent="addComment" class="pt-3 border-t border-slate-100 dark:border-slate-700/40">
                                        <div class="flex gap-3">
                                            <div class="flex-shrink-0 mt-1">
                                                <div class="h-8 w-8 rounded-full bg-gradient-to-br from-sky-400 to-cyan-500 text-white flex items-center justify-center text-[10px] font-bold uppercase shadow-sm">
                                                    {{ substr($client->name, 0, 2) }}
                                                </div>
                                            </div>
                                            <div class="flex-1 space-y-2">
                                                <textarea wire:model="newCommentContent" rows="3" placeholder="Add a comment..."
                                                    class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all resize-none"></textarea>
                                                @error('newCommentContent') <span class="text-[10px] text-rose-500 block">{{ $message }}</span> @enderror
                                                <div class="flex justify-end">
                                                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition-all cursor-pointer shadow-sm">
                                                        <i class="fa-solid fa-paper-plane mr-1.5 text-[10px]"></i> Save
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                @endif

                            </div>
                        </div>

                        {{-- RIGHT PANEL: Details Sidebar --}}
                        <div class="lg:w-[38%] flex flex-col bg-slate-50/80 dark:bg-slate-800/30 min-h-0">
                            <div class="flex-1 overflow-y-auto custom-scroll px-5 py-5 space-y-5">

                                {{-- Status --}}
                                <div class="space-y-2">
                                    <h4 class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">Status</h4>
                                    @php
                                        $statusConfig = [
                                            'todo' => ['label' => 'To Do', 'bg' => 'bg-slate-200 dark:bg-slate-700', 'text' => 'text-slate-600 dark:text-slate-300'],
                                            'in_progress' => ['label' => 'In Progress', 'bg' => 'bg-sky-100 dark:bg-sky-900/50', 'text' => 'text-sky-700 dark:text-sky-300'],
                                            'review' => ['label' => 'In Review', 'bg' => 'bg-amber-100 dark:bg-amber-900/50', 'text' => 'text-amber-700 dark:text-amber-300'],
                                            'done' => ['label' => 'Done', 'bg' => 'bg-emerald-100 dark:bg-emerald-900/50', 'text' => 'text-emerald-700 dark:text-emerald-300'],
                                        ];
                                        $sc = $statusConfig[$viewTask->status] ?? $statusConfig['todo'];
                                    @endphp
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold {{ $sc['bg'] }} {{ $sc['text'] }}">
                                        {{ $sc['label'] }}
                                    </span>
                                </div>

                                {{-- Progress Steps --}}
                                <div class="space-y-2">
                                    <h4 class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">Progress</h4>
                                    @php
                                        $statusSteps = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'done' => 'Done'];
                                        $statusOrder = array_keys($statusSteps);
                                        $currentIndex = array_search($viewTask->status, $statusOrder);
                                    @endphp
                                    <div class="space-y-0">
                                        @foreach($statusSteps as $key => $label)
                                            @php $stepIndex = array_search($key, $statusOrder); @endphp
                                            <div class="flex items-center gap-3 py-1.5">
                                                <div class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold flex-shrink-0
                                                    {{ $stepIndex < $currentIndex ? 'bg-emerald-500 text-white' : ($stepIndex === $currentIndex ? 'bg-indigo-500 text-white ring-2 ring-indigo-500/25' : 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500') }}">
                                                    @if($stepIndex < $currentIndex)
                                                        <i class="fa-solid fa-check"></i>
                                                    @else
                                                        {{ $stepIndex + 1 }}
                                                    @endif
                                                </div>
                                                <span class="text-xs font-medium {{ $stepIndex <= $currentIndex ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400 dark:text-slate-500' }}">{{ $label }}</span>
                                                @if($stepIndex === $currentIndex)
                                                    <span class="text-[8px] font-bold text-indigo-500 dark:text-indigo-400 uppercase tracking-wider">Current</span>
                                                @endif
                                            </div>
                                            @if(!$loop->last)
                                                <div class="ml-2.5 w-px h-3 {{ $stepIndex < $currentIndex ? 'bg-emerald-400' : 'bg-slate-200 dark:bg-slate-700' }}"></div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Priority --}}
                                <div class="space-y-2">
                                    <h4 class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">Priority</h4>
                                    @php
                                        $priorityConfig = [
                                            'critical' => ['icon' => 'fa-solid fa-arrow-up', 'color' => 'text-rose-500', 'label' => 'Critical'],
                                            'high' => ['icon' => 'fa-solid fa-arrow-up', 'color' => 'text-orange-500', 'label' => 'High'],
                                            'medium' => ['icon' => 'fa-solid fa-equals', 'color' => 'text-sky-500', 'label' => 'Medium'],
                                            'low' => ['icon' => 'fa-solid fa-arrow-down', 'color' => 'text-emerald-500', 'label' => 'Low'],
                                        ];
                                        $pc = $priorityConfig[$viewTask->priority] ?? $priorityConfig['medium'];
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        <i class="{{ $pc['icon'] }} {{ $pc['color'] }} text-xs"></i>
                                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $pc['label'] }}</span>
                                    </div>
                                </div>

                                <hr class="border-slate-200 dark:border-slate-700/50">

                                {{-- Details Fields --}}
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">Created</span>
                                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ $viewTask->created_at->format('M d, Y H:i') }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">Updated</span>
                                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ $viewTask->updated_at->diffForHumans() }}</span>
                                    </div>
                                    @if($viewTask->due_date)
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">Due Date</span>
                                        <span class="text-xs font-medium {{ $viewTask->due_date->isPast() ? 'text-rose-500' : 'text-slate-700 dark:text-slate-300' }}">
                                            {{ $viewTask->due_date->format('M d, Y') }}
                                        </span>
                                    </div>
                                    @endif
                                    @if($viewTask->assignee)
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">Assignee</span>
                                        <div class="flex items-center gap-1.5">
                                            <div class="h-5 w-5 rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 text-white flex items-center justify-center text-[8px] font-bold uppercase">
                                                {{ substr($viewTask->assignee->name, 0, 2) }}
                                            </div>
                                            <span class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ $viewTask->assignee->name }}</span>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
