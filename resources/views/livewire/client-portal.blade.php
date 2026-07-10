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

                        <!-- Description -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Description <span class="text-rose-500">*</span></label>
                            <textarea wire:model="description"
                                      x-on:input="localStorage.setItem('portal_draft_description', $event.target.value)"
                                      rows="5"
                                      placeholder="Describe your issue or request in detail. The more details you provide, the faster we can help..."
                                      class="w-full px-4 py-3 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-white/10 rounded-xl text-sm text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all duration-200 resize-none"></textarea>
                            @error('description') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

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
    {{-- TICKET DETAIL MODAL --}}
    {{-- ============================================ --}}
    @if($showTaskModal && $viewTaskId)
        @php
            $viewTask = \App\Models\Task::with(['comments.replies', 'media'])->find($viewTaskId);
        @endphp
        @if($viewTask)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm animate-fade-in" @keydown.escape.window="$wire.closeModal()">
                <div class="bg-white dark:bg-slate-900 w-full max-w-4xl rounded-2xl max-h-[90vh] overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-700 flex flex-col" @click.away="$wire.closeModal()">

                    <!-- Modal Header -->
                    <div class="flex items-start justify-between p-5 border-b border-slate-200 dark:border-white/5 flex-shrink-0">
                        <div class="space-y-2 pr-8 overflow-hidden">
                            <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 bg-slate-100 dark:bg-white/5 px-2 py-0.5 rounded">#{{ $viewTask->id }}</span>
                                <span class="text-[9px] font-bold uppercase tracking-widest text-indigo-500 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-500/20 px-2 py-0.5 rounded-full">
                                    {{ $viewTask->project ? $viewTask->project->name : 'General' }}
                                </span>
                                @if($viewTask->status === 'todo')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">To Do</span>
                                @elseif($viewTask->status === 'in_progress')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-sky-100 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400">In Progress</span>
                                @elseif($viewTask->status === 'review')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400">Review</span>
                                @elseif($viewTask->status === 'done')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400">Done</span>
                                @endif
                                <!-- Priority -->
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold
                                    {{ $viewTask->priority === 'critical' ? 'bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400' :
                                       ($viewTask->priority === 'high' ? 'bg-orange-100 dark:bg-orange-950/40 text-orange-600 dark:text-orange-400' :
                                       ($viewTask->priority === 'medium' ? 'bg-sky-100 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400' :
                                       'bg-emerald-100 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400')) }}">
                                    {{ ucfirst($viewTask->priority) }}
                                </span>
                            </div>
                            <h2 class="text-lg font-bold font-outfit text-slate-900 dark:text-white leading-tight tracking-tight">{{ $viewTask->title }}</h2>
                        </div>
                        <button type="button" wire:click="closeModal" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5 transition-all cursor-pointer flex-shrink-0">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <!-- Status Progress Bar -->
                    @php
                        $statusSteps = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'done' => 'Done'];
                        $statusOrder = array_keys($statusSteps);
                        $currentIndex = array_search($viewTask->status, $statusOrder);
                    @endphp
                    <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-700/50 flex-shrink-0 bg-slate-50 dark:bg-slate-800/30">
                        <div class="flex items-center">
                            @foreach($statusSteps as $key => $label)
                                @php $stepIndex = array_search($key, $statusOrder); @endphp
                                <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-[9px] font-bold flex-shrink-0
                                            {{ $stepIndex < $currentIndex ? 'bg-emerald-500 text-white' : ($stepIndex === $currentIndex ? 'bg-indigo-500 text-white ring-4 ring-indigo-500/20' : 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500') }}">
                                            @if($stepIndex < $currentIndex)
                                                <i class="fa-solid fa-check"></i>
                                            @else
                                                {{ $stepIndex + 1 }}
                                            @endif
                                        </div>
                                        <span class="text-[11px] font-semibold whitespace-nowrap {{ $stepIndex <= $currentIndex ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400 dark:text-slate-500' }}">{{ $label }}</span>
                                    </div>
                                    @if(!$loop->last)
                                        <div class="flex-1 h-0.5 mx-3 rounded {{ $stepIndex < $currentIndex ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700' }}"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Modal Body: 2-Column -->
                    <div class="flex-1 overflow-hidden flex flex-col md:flex-row">
                        <!-- Left: Ticket Info -->
                        <div class="md:w-3/5 p-5 overflow-y-auto custom-scroll space-y-4 border-r border-slate-100 dark:border-slate-700/50">
                            <!-- Description -->
                            <div class="space-y-2">
                                <h4 class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">Description</h4>
                                <div class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-700/50 p-4 rounded-xl whitespace-pre-wrap">{{ $viewTask->description ?: 'No description provided.' }}</div>
                            </div>

                            @if(config('features.task_attachments', true) && $viewTask->hasMedia('documents'))
                                <!-- Attachments -->
                                <div class="space-y-2">
                                    <h4 class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">Attachments</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @foreach($viewTask->getMedia('documents') as $media)
                                            <a href="{{ Storage::url($media->id . '/' . $media->file_name) }}" target="_blank"
                                               class="flex items-center space-x-2 p-3 bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/5 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">
                                                <i class="fa-solid fa-file text-slate-400 text-sm"></i>
                                                <div class="overflow-hidden">
                                                    <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 truncate">{{ $media->file_name }}</p>
                                                    <p class="text-[9px] text-slate-400">{{ number_format($media->size / 1024, 1) }} KB</p>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Meta Info -->
                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <div class="bg-slate-50 dark:bg-white/3 rounded-xl p-3 space-y-1">
                                    <span class="text-[9px] uppercase font-bold text-slate-400 tracking-wider">Created</span>
                                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $viewTask->created_at->format('M d, Y H:i') }}</p>
                                </div>
                                <div class="bg-slate-50 dark:bg-white/3 rounded-xl p-3 space-y-1">
                                    <span class="text-[9px] uppercase font-bold text-slate-400 tracking-wider">Last Updated</span>
                                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $viewTask->updated_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Discussion Chat -->
                        @if(config('features.client_portal_comments', true))
                            <div class="md:w-2/5 flex flex-col bg-slate-50 dark:bg-slate-800/50">
                                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700/50">
                                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center space-x-1.5">
                                        <i class="fa-regular fa-comments text-slate-400"></i>
                                        <span>Discussion</span>
                                        <span class="text-[9px] bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400 px-1.5 py-0.5 rounded-full font-bold">
                                            {{ $viewTask->comments()->where('is_private', false)->count() + $viewTask->allComments()->where('is_private', false)->whereNotNull('parent_id')->count() }}
                                        </span>
                                    </h4>
                                </div>

                                <!-- Chat Messages -->
                                <div class="flex-1 overflow-y-auto custom-scroll p-4 space-y-4 max-h-[450px]">
                                    @forelse($viewTask->comments()->where('is_private', false)->get() as $comment)
                                        @php $isClient = !empty($comment->client_id); @endphp
                                        <div class="flex flex-col {{ $isClient ? 'items-end' : 'items-start' }} w-full space-y-1">
                                            <!-- Message Header -->
                                            <div class="flex items-center space-x-1.5 text-[10px] text-slate-400 dark:text-slate-500">
                                                @if(!$isClient)
                                                    <div class="h-5 w-5 rounded-full bg-gradient-to-tr from-indigo-400 to-violet-500 text-white flex items-center justify-center text-[8px] font-bold uppercase flex-shrink-0">
                                                        {{ substr($comment->user ? $comment->user->name : 'S', 0, 2) }}
                                                    </div>
                                                    <span class="font-bold text-slate-700 dark:text-slate-300">
                                                        {{ $comment->user ? $comment->user->name : 'System' }}
                                                    </span>
                                                @else
                                                    <span class="font-bold text-slate-700 dark:text-slate-300">
                                                        {{ $comment->client->name }}
                                                    </span>
                                                @endif
                                                <span>·</span>
                                                <span>{{ $comment->created_at->diffForHumans() }}</span>
                                                @if($isClient)
                                                    <div class="h-5 w-5 rounded-full bg-gradient-to-tr from-sky-400 to-cyan-500 text-white flex items-center justify-center text-[8px] font-bold uppercase flex-shrink-0">
                                                        {{ substr($comment->client->name, 0, 2) }}
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Message Bubble -->
                                            <div class="max-w-[85%] text-left">
                                                <div class="inline-block text-xs leading-relaxed p-3 rounded-2xl shadow-sm whitespace-pre-wrap text-left
                                                    {{ $isClient 
                                                        ? 'bg-indigo-600 text-white rounded-tr-none' 
                                                        : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/80 rounded-tl-none' }}">
                                                    {!! $comment->formatted_content !!}
                                                </div>
                                            </div>

                                            <!-- Nested Replies -->
                                            @if($comment->replies()->where('is_private', false)->count() > 0)
                                                <div class="w-full flex flex-col {{ $isClient ? 'items-end' : 'items-start' }} space-y-2 mt-1">
                                                    @foreach($comment->replies()->where('is_private', false)->get() as $reply)
                                                        @php $isReplyClient = !empty($reply->client_id); @endphp
                                                        <div class="flex flex-col {{ $isReplyClient ? 'items-end' : 'items-start' }} w-full space-y-0.5">
                                                            <div class="flex items-center space-x-1 text-[9px] text-slate-400 dark:text-slate-500">
                                                                <span class="font-bold">
                                                                    {{ $reply->user ? $reply->user->name : ($reply->client ? $reply->client->name : 'System') }}
                                                                </span>
                                                                <span>·</span>
                                                                <span>{{ $reply->created_at->diffForHumans() }}</span>
                                                            </div>
                                                            <div class="max-w-[80%] text-left">
                                                                <div class="inline-block text-[11px] leading-relaxed p-2 rounded-xl shadow-sm whitespace-pre-wrap text-left
                                                                    {{ $isReplyClient 
                                                                        ? 'bg-indigo-500 text-white rounded-tr-none' 
                                                                        : 'bg-slate-100 dark:bg-slate-850 text-slate-600 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700/60 rounded-tl-none' }}">
                                                                    {!! $reply->formatted_content !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <!-- Reply toggle button & form -->
                                            <div x-data="{ showReply: false }" class="pt-0.5 w-full flex flex-col {{ $isClient ? 'items-end' : 'items-start' }}">
                                                <button type="button" @click="showReply = !showReply" class="text-[9px] font-bold text-indigo-500 hover:text-indigo-400 transition-colors cursor-pointer focus:outline-none">
                                                    <i class="fa-solid fa-reply mr-0.5"></i> Reply
                                                </button>
                                                <div x-show="showReply" x-transition class="mt-2 w-full max-w-[85%] space-y-1.5 animate-fade-in">
                                                    <textarea wire:model="replyCommentContent.{{ $comment->id }}" rows="2" placeholder="Write a reply..." class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all resize-none"></textarea>
                                                    <div class="flex justify-end">
                                                        <button type="button" wire:click="addReply({{ $comment->id }})" @click="showReply = false" class="px-3 py-1.5 bg-indigo-500 hover:bg-indigo-600 text-white font-bold rounded-lg text-[10px] transition-all cursor-pointer">
                                                            Send
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center py-12 text-slate-400 dark:text-slate-500">
                                            <i class="fa-regular fa-comments text-2xl mb-2 block"></i>
                                            <p class="text-xs">No messages yet. Start the conversation!</p>
                                        </div>
                                    @endforelse
                                </div>

                                <!-- Message Input (sticky bottom) -->
                                <div class="p-3 border-t border-slate-200 dark:border-slate-700/50 bg-white dark:bg-slate-900">
                                    <form wire:submit.prevent="addComment" class="flex items-end space-x-2">
                                        <textarea wire:model="newCommentContent" rows="2" placeholder="Type a message..."
                                                  class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all resize-none"></textarea>
                                        <button type="submit" class="p-2.5 bg-gradient-to-r from-sky-500 to-indigo-600 text-white rounded-xl hover:from-sky-400 hover:to-indigo-500 transition-all cursor-pointer shadow-md flex-shrink-0">
                                            <i class="fa-solid fa-paper-plane text-xs"></i>
                                        </button>
                                    </form>
                                    @error('newCommentContent') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
