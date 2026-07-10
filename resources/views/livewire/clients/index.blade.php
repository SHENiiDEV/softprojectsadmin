<div class="space-y-6">
    <x-slot name="header">
        Clients
    </x-slot>

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="font-outfit font-extrabold text-2xl text-slate-800 dark:text-white tracking-tight">Clients Registry</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage clients and links to personal Jira support portals.</p>
        </div>
        @if(auth()->user()->hasAnyRole(['admin', 'manager']))
            <div>
                <button wire:click="openCreateModal" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-sky-700 bg-sky-100 hover:bg-sky-200 dark:bg-sky-950/40 dark:text-sky-400 border border-sky-200/40 dark:border-sky-900/40 rounded-xl shadow-sm hover:-translate-y-0.5 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Client
                </button>
            </div>
        @endif
    </div>

    <!-- Alert Messages -->
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-400 flex items-center justify-between shadow-sm">
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
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-800/40 text-rose-800 dark:text-rose-400 flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 dark:text-rose-400 text-lg"></i>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
            <button @click="show = false" class="text-rose-500 hover:text-rose-700 dark:hover:text-rose-300">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- Search Controls -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
        <div class="relative max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass"></i>
            </span>
            <input type="text" 
                   wire:model.live.debounce.300ms="search" 
                   placeholder="Search by client name..." 
                   class="w-full pl-11 pr-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200">
        </div>
    </div>

    <!-- Clients List Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-slate-400 dark:text-slate-500 text-xs font-semibold uppercase border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-950/20">
                        <th class="py-4 px-6">Client Name</th>
                        <th class="py-4 px-6">Companies</th>
                        <th class="py-4 px-6">Portal Link (Jira Support)</th>
                        @if(auth()->user()->hasAnyRole(['admin', 'manager']))
                            <th class="py-4 px-6 text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-sm">
                    @forelse($clients as $client)
                        @php
                            $portalUrl = url('/portal/' . $client->hash);
                            $btnId = 'copy-btn-' . $client->id;
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors duration-150">
                            <!-- Client Name -->
                            <td class="py-4 px-6 font-semibold text-slate-800 dark:text-slate-200">
                                {{ $client->name }}
                            </td>
                            <!-- Companies Count -->
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200">
                                    {{ $client->companies_count }}
                                </span>
                            </td>
                            <!-- Portal link -->
                            <td class="py-4 px-6">
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-950 px-2.5 py-1.5 rounded-lg border border-slate-100 dark:border-slate-800 select-all max-w-xs truncate" title="{{ $portalUrl }}">
                                        {{ $portalUrl }}
                                    </span>
                                    <button type="button" 
                                            id="{{ $btnId }}"
                                            onclick="window.copyPortalLink('{{ $portalUrl }}', '{{ $btnId }}')" 
                                            class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-100 hover:bg-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200/40 dark:border-indigo-900/40 rounded-lg shadow-sm transition-all duration-150 cursor-pointer">
                                        <i class="fa-solid fa-copy mr-1"></i> Copy
                                    </button>
                                </div>
                            </td>
                            <!-- Actions -->
                            @if(auth()->user()->hasAnyRole(['admin', 'manager']))
                                <td class="py-4 px-6 text-right space-x-1.5 whitespace-nowrap">
                                    <!-- Edit -->
                                    <button type="button" 
                                            wire:click="openEditModal({{ $client->id }})" 
                                            class="inline-flex items-center justify-center p-2 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-sky-600 dark:hover:text-sky-400 transition-colors duration-150 cursor-pointer" 
                                            title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <!-- Delete -->
                                    <button type="button" 
                                            wire:click="deleteClient({{ $client->id }})" 
                                            wire:confirm="Are you sure you want to delete this client? All their companies will be unlinked from the client."
                                            class="inline-flex items-center justify-center p-2 rounded-lg text-slate-400 hover:bg-rose-50 dark:hover:bg-rose-950/20 hover:text-rose-600 dark:hover:text-rose-400 transition-colors duration-150 cursor-pointer" 
                                            title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <i class="fa-solid fa-users-slash text-3xl text-slate-300 dark:text-slate-700"></i>
                                    <span class="text-sm">No clients found matching the search criteria.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($clients->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
                {{ $clients->links() }}
            </div>
        @endif
    </div>

    <!-- Client Modal (Glassmorphic) -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay background -->
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="$set('showModal', false)"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal Panel -->
                <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-100 dark:border-slate-800/80">
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/20">
                        <h3 class="text-base font-bold text-slate-800 dark:text-white font-outfit" id="modal-title">
                            {{ $clientId ? 'Edit Client' : 'Add Client' }}
                        </h3>
                        <button type="button" wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors focus:outline-none cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <form wire:submit.prevent="saveClient">
                        <div class="px-6 py-6 space-y-4">
                            <!-- Name -->
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Client Name <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="name" placeholder="e.g., APS, Chilly, Marvli" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                                @error('name') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/20 flex items-center justify-end space-x-2">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl transition-all duration-150 cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100/80 dark:bg-indigo-950/30 dark:text-indigo-400 border border-indigo-200/40 dark:border-indigo-800/40 rounded-xl transition-all duration-150 cursor-pointer">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script>
        if (typeof window.copyPortalLink === 'undefined') {
            window.copyPortalLink = function(url, buttonId) {
                navigator.clipboard.writeText(url).then(() => {
                    const btn = document.getElementById(buttonId);
                    if (!btn) return;
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="fa-solid fa-check mr-1"></i> Copied';
                    btn.classList.remove('bg-indigo-100', 'text-indigo-700', 'dark:bg-indigo-950/40', 'dark:text-indigo-400');
                    btn.classList.add('bg-emerald-100', 'text-emerald-700', 'dark:bg-emerald-950/40', 'dark:text-emerald-400');
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                        btn.classList.remove('bg-emerald-100', 'text-emerald-700', 'dark:bg-emerald-950/40', 'dark:text-emerald-400');
                        btn.classList.add('bg-indigo-100', 'text-indigo-700', 'dark:bg-indigo-950/40', 'dark:text-indigo-400');
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy link: ', err);
                });
            };
        }
    </script>
</div>
