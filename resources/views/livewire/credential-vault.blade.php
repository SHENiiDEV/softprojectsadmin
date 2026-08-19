<div class="space-y-6" @copy-to-clipboard.window="if ($event.detail && $event.detail.value) { navigator.clipboard.writeText($event.detail.value); }">

    {{-- ═══════════════════════════════════════════════════════════
         HEADER
    ═══════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Credential Vault</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Credentials across all companies — {{ $credentials->count() }} records
            </p>
        </div>

        <div class="flex items-center gap-3">
            {{-- Add Credential Action --}}
            <button wire:click="openCreateModal(null)" class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-white bg-sky-600 hover:bg-sky-500 rounded-xl shadow-sm transition-all duration-150 cursor-pointer">
                <i class="fa-solid fa-plus mr-1.5"></i> Add Credential
            </button>

            {{-- Import JSON Action --}}
            <button wire:click="openImportModal" class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl shadow-sm transition-all duration-150 cursor-pointer">
                <i class="fa-solid fa-file-import mr-1.5 text-sky-500"></i> Import JSON
            </button>

            {{-- Group by toggle --}}
            <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 rounded-xl p-1">
                <button wire:click="$set('groupBy', 'project')"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200 cursor-pointer
                               {{ $groupBy === 'project'
                                   ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm'
                                   : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                    By Company
                </button>
                <button wire:click="$set('groupBy', 'type')"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200 cursor-pointer
                               {{ $groupBy === 'type'
                                   ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm'
                                   : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                    By Type
                </button>
            </div>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-400 flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400 text-lg"></i>
                <span class="text-sm font-medium">{{ session('message') }}</span>
            </div>
            <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-800/40 text-rose-800 dark:text-rose-400 flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 dark:text-rose-400 text-lg"></i>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
            <button @click="show = false" class="text-rose-500 hover:text-rose-700 dark:hover:text-rose-300">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         FILTERS
    ═══════════════════════════════════════════════════════════ --}}
    <div class="flex flex-wrap items-center gap-2">

        {{-- Search --}}
        <div class="relative flex-1 min-w-64">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text"
                   wire:model.live.debounce.250ms="search"
                   placeholder="Search by name, login, company..."
                   class="w-full pl-9 pr-4 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all placeholder:text-slate-400">
            @if($search)
                <button wire:click="$set('search', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                    ✕
                </button>
            @endif
        </div>

        {{-- Type Filter --}}
        <select wire:model.live="filterType"
                class="px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
            <option value="">All Types</option>
            @foreach(['cms', 'hosting', 'db', 'payment_gateway', 'ssh', 'email', 'other'] as $t)
                <option value="{{ $t }}">{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
            @endforeach
        </select>

        {{-- Company Filter --}}
        <select wire:model.live="filterProject"
                class="px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all max-w-48 truncate">
            <option value="">All Companies</option>
            @foreach($projects as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
        </select>

        @if($search || $filterType || $filterProject)
            <button wire:click="$set('search', ''); $set('filterType', ''); $set('filterProject', '')"
                    class="px-3 py-2 text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 dark:bg-rose-950/30 rounded-xl transition-all">
                Reset
            </button>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         CREDENTIALS GROUPS
    ═══════════════════════════════════════════════════════════ --}}
    @if($grouped->isEmpty())
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-12 text-center shadow-sm">
            <i class="fa-solid fa-key text-3xl text-slate-300 dark:text-slate-700 mb-3"></i>
            <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">No credentials found</p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Try adjusting your filters or add new credentials.</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($grouped as $group)
                <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">

                    {{-- Group Header --}}
                    <div class="px-5 py-3.5 bg-slate-50/70 dark:bg-slate-950/40 border-b border-slate-100 dark:border-slate-800/60 flex items-center justify-between">
                        <div class="flex items-center space-x-2.5">
                            @if($groupBy === 'type')
                                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                                <h3 class="font-outfit font-bold text-sm text-slate-800 dark:text-white capitalize">
                                    {{ str_replace('_', ' ', $group['label']) }}
                                </h3>
                            @else
                                <i class="fa-solid fa-building text-indigo-500 text-xs"></i>
                                <h3 class="font-outfit font-bold text-sm text-slate-800 dark:text-white">
                                    {{ $group['label'] }}
                                </h3>
                            @endif
                        </div>

                        {{-- Action & Counter Badge --}}
                        <div class="flex items-center space-x-2">
                            @if($groupBy === 'project' && !empty($group['project_id']))
                                <button wire:click="openCreateModal({{ $group['project_id'] }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-sky-600 dark:text-sky-400 bg-sky-50 hover:bg-sky-100 dark:bg-sky-950/40 dark:hover:bg-sky-900/60 border border-sky-200 dark:border-sky-800 rounded-xl transition-all duration-150 cursor-pointer shadow-sm">
                                    <i class="fa-solid fa-plus text-[10px]"></i> Add New
                                </button>
                            @endif

                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200/60 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                {{ $group['items']->count() }} {{ Str::plural('record', $group['items']->count()) }}
                            </span>
                        </div>
                    </div>

                    {{-- Items Grid --}}
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($group['items'] as $credential)
                            @php
                                $typeColor = match($credential->type) {
                                    'cms' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border-emerald-200/60',
                                    'hosting' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border-indigo-200/60',
                                    'db' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border-amber-200/60',
                                    'payment_gateway' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-400 border-purple-200/60',
                                    default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200/60',
                                };
                            @endphp

                            <div wire:click="openCredential({{ $credential->id }})"
                                 class="group p-3.5 rounded-xl bg-slate-50/50 dark:bg-slate-950/20 border border-slate-200/60 dark:border-slate-800/80 hover:border-sky-400 dark:hover:border-sky-600 hover:shadow-md transition-all duration-150 cursor-pointer space-y-2">

                                <div class="flex items-start justify-between gap-2">
                                    <h4 class="font-bold text-xs text-slate-800 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors line-clamp-1">
                                        {{ $credential->name ?: 'Access Details' }}
                                    </h4>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold border uppercase {{ $typeColor }}">
                                        {{ str_replace('_', ' ', $credential->type) }}
                                    </span>
                                </div>

                                @if($groupBy === 'type' && $credential->project)
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                        <i class="fa-solid fa-building text-slate-400"></i>
                                        <span class="truncate">{{ $credential->project->name }}</span>
                                    </p>
                                @endif

                                @if($credential->login)
                                    <p class="text-xs font-mono text-slate-600 dark:text-slate-400 flex items-center gap-1.5 truncate">
                                        <i class="fa-solid fa-user text-slate-400 text-[10px]"></i>
                                        <span class="truncate">{{ $credential->login }}</span>
                                    </p>
                                @endif

                                @if($credential->provider_url)
                                    <p class="text-[11px] font-mono text-sky-600 dark:text-sky-400 truncate flex items-center gap-1.5">
                                        <i class="fa-solid fa-link text-[10px]"></i>
                                        <span class="truncate">{{ $credential->provider_url }}</span>
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                </div>
            @endforeach
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         CREATE CREDENTIAL MODAL
    ═══════════════════════════════════════════════════════════ --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="create-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="closeCreateModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100 dark:border-slate-800/80">
                    
                    {{-- Modal Header --}}
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/20">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-sky-600 dark:text-sky-400">
                                Vault Management
                            </span>
                            <h3 class="text-base font-bold text-slate-800 dark:text-white font-outfit" id="create-modal-title">
                                Add New Credential {{ $newProjectName ? "for {$newProjectName}" : '' }}
                            </h3>
                        </div>
                        <button type="button" wire:click="closeCreateModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors focus:outline-none cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    {{-- Modal Body Form --}}
                    <form wire:submit.prevent="saveNewCredential">
                        <div class="px-6 py-6 space-y-4">
                            
                            {{-- Company Selection --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Company</label>
                                <select wire:model.live="newProjectId" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                                    @foreach($projects as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                                @error('newProjectId') <span class="text-xs text-rose-500 block mt-1">{{ $message }}</span> @enderror
                            </div>

                            {{-- Credential Name & Type --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Name</label>
                                    <input type="text" wire:model="newName" placeholder="e.g. WordPress CMS Admin" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                                    @error('newName') <span class="text-xs text-rose-500 block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Type</label>
                                    <select wire:model="newType" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                                        @foreach(['cms', 'hosting', 'db', 'payment_gateway', 'ssh', 'email', 'other'] as $t)
                                            <option value="{{ $t }}">{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                                        @endforeach
                                    </select>
                                    @error('newType') <span class="text-xs text-rose-500 block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- Provider URL --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Provider URL / Portal (Optional)</label>
                                <input type="url" wire:model="newProviderUrl" placeholder="https://example.com/login" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-mono text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                                @error('newProviderUrl') <span class="text-xs text-rose-500 block mt-1">{{ $message }}</span> @enderror
                            </div>

                            {{-- Website Link (Optional) --}}
                            @if($companyWebsites->isNotEmpty())
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Associated Website (Optional)</label>
                                    <select wire:model="newWebsiteId" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                                        <option value="">None</option>
                                        @foreach($companyWebsites as $w)
                                            <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->url }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- Login & Password --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Login / Username</label>
                                    <input type="text" wire:model="newLogin" placeholder="admin@company.com" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-mono text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                                    @error('newLogin') <span class="text-xs text-rose-500 block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                                    <input type="text" wire:model="newPassword" placeholder="••••••••" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-mono text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                                    @error('newPassword') <span class="text-xs text-rose-500 block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- Comments --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Comments / Notes (Optional)</label>
                                <textarea wire:model="newComments" rows="3" placeholder="2FA backup codes, IP restrictions..." class="w-full text-xs p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500"></textarea>
                                @error('newComments') <span class="text-xs text-rose-500 block mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/20 flex items-center justify-end space-x-2">
                            <button type="button" wire:click="closeCreateModal" class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl transition-all duration-150 cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" wire:loading.attr="disabled" class="px-4 py-2 text-xs font-semibold text-white bg-sky-600 hover:bg-sky-500 rounded-xl transition-all duration-150 cursor-pointer shadow-sm">
                                <span wire:loading.remove wire:target="saveNewCredential">Save Credential</span>
                                <span wire:loading wire:target="saveNewCredential">Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         VIEW CREDENTIAL MODAL
    ═══════════════════════════════════════════════════════════ --}}
    @if($showModal && $selectedCredential)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="closeModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100 dark:border-slate-800/80">
                    
                    {{-- Modal Header --}}
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/20">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-sky-600 dark:text-sky-400">
                                {{ str_replace('_', ' ', $selectedCredential->type) }}
                            </span>
                            <h3 class="text-base font-bold text-slate-800 dark:text-white font-outfit" id="modal-title">
                                {{ $selectedCredential->name ?: 'Credential Vault Entry' }}
                            </h3>
                        </div>
                        <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors focus:outline-none cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="px-6 py-6 space-y-4">
                        {{-- Company --}}
                        @if($selectedCredential->project)
                            <div>
                                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Company</span>
                                <a href="{{ route('projects.show', $selectedCredential->project_id) }}" class="text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline flex items-center gap-1.5">
                                    <i class="fa-solid fa-building"></i>
                                    {{ $selectedCredential->project->name }}
                                </a>
                            </div>
                        @endif

                        {{-- Provider URL --}}
                        @if($selectedCredential->provider_url)
                            <div>
                                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Provider URL / Portal</span>
                                <a href="{{ $selectedCredential->provider_url }}" target="_blank" class="text-xs font-mono text-sky-600 dark:text-sky-400 hover:underline flex items-center gap-1.5 truncate">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    {{ $selectedCredential->provider_url }}
                                </a>
                            </div>
                        @endif

                        {{-- Login --}}
                        @if($selectedCredential->login)
                            <div>
                                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Login / Username</span>
                                <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl">
                                    <span class="font-mono text-xs text-slate-800 dark:text-slate-200 select-all">{{ $selectedCredential->login }}</span>
                                    <button type="button"
                                            x-data="{ copied: false }"
                                            @click="navigator.clipboard.writeText('{{ addslashes($selectedCredential->login) }}'); copied = true; setTimeout(() => copied = false, 2000); $wire.copyToClipboard('login')"
                                            class="text-slate-400 hover:text-sky-500 text-xs px-2 py-1 rounded transition-colors flex items-center gap-1 cursor-pointer"
                                            title="Copy Login">
                                        <i class="fa-solid" :class="copied ? 'fa-check text-emerald-500' : 'fa-copy'"></i>
                                        <span x-show="copied" x-cloak class="text-[10px] text-emerald-500 font-semibold">Copied!</span>
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- Password with Reveal --}}
                        @if($selectedCredential->password)
                            <div>
                                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Password</span>
                                <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl">
                                    <span class="font-mono text-xs text-slate-800 dark:text-slate-200 select-all">
                                        {{ $showPassword ? $selectedCredential->password : '••••••••••••••••' }}
                                    </span>
                                    <div class="flex items-center space-x-1">
                                        <button wire:click="togglePassword" class="text-slate-400 hover:text-sky-500 text-xs p-1.5 rounded transition-colors cursor-pointer" title="{{ $showPassword ? 'Hide Password' : 'Show Password' }}">
                                            <i class="fa-solid {{ $showPassword ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                        </button>
                                        <button type="button"
                                                x-data="{ copied: false }"
                                                @click="navigator.clipboard.writeText('{{ addslashes($selectedCredential->password) }}'); copied = true; setTimeout(() => copied = false, 2000); $wire.copyToClipboard('password')"
                                                class="text-slate-400 hover:text-sky-500 text-xs p-1.5 rounded transition-colors flex items-center gap-1 cursor-pointer"
                                                title="Copy Password">
                                            <i class="fa-solid" :class="copied ? 'fa-check text-emerald-500' : 'fa-copy'"></i>
                                            <span x-show="copied" x-cloak class="text-[10px] text-emerald-500 font-semibold">Copied!</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Comments --}}
                        @if($selectedCredential->comments)
                            <div>
                                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Comments / Notes</span>
                                <div class="p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap">
                                    {{ $selectedCredential->comments }}
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/20 flex items-center justify-end">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl transition-all duration-150 cursor-pointer">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         IMPORT JSON MODAL
    ═══════════════════════════════════════════════════════════ --}}
    @if($showImportModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="import-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="closeImportModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100 dark:border-slate-800/80">
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/20">
                        <h3 class="text-base font-bold text-slate-800 dark:text-white font-outfit flex items-center gap-2" id="import-modal-title">
                            <i class="fa-solid fa-file-import text-sky-500"></i> Import Credentials (JSON)
                        </h3>
                        <button type="button" wire:click="closeImportModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors focus:outline-none cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <form wire:submit.prevent="processImport">
                        <div class="px-6 py-6 space-y-5">
                            <!-- Option 1: File Upload -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Option 1: Upload JSON File</label>
                                <input type="file" wire:model="importFile" accept=".json" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 dark:file:bg-sky-950/40 dark:file:text-sky-400 cursor-pointer">
                                @error('importFile') <span class="text-xs text-rose-500 block mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="relative flex py-1 items-center">
                                <div class="flex-grow border-t border-slate-200 dark:border-slate-800"></div>
                                <span class="flex-shrink mx-4 text-slate-400 text-xs font-semibold uppercase">OR</span>
                                <div class="flex-grow border-t border-slate-200 dark:border-slate-800"></div>
                            </div>

                            <!-- Option 2: Paste Raw JSON -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Option 2: Paste JSON Content</label>
                                <textarea wire:model="rawJsonInput" rows="6" placeholder='[{"company_name": "Company LTD", "name": "CMS Admin", "type": "cms", "login": "admin", "password": "123"}]' class="w-full font-mono text-xs p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500"></textarea>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/20 flex items-center justify-end space-x-2">
                            <button type="button" wire:click="closeImportModal" class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl transition-all duration-150 cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" wire:loading.attr="disabled" class="px-4 py-2 text-xs font-semibold text-white bg-sky-600 hover:bg-sky-500 rounded-xl transition-all duration-150 cursor-pointer shadow-sm">
                                <span wire:loading.remove wire:target="processImport">Start Import</span>
                                <span wire:loading wire:target="processImport">Importing...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
