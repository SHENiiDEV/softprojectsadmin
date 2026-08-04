<div class="space-y-6">
    <!-- Alert Message -->
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition:leave="transition ease-in duration-300 transform" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-400 flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-3">
                <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-medium">{{ session('message') }}</span>
            </div>
            <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- Header Section -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60">
        <div>
            <h3 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Company Websites</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage all web resources and domains for this project.</p>
        </div>
        @if(!$showForm)
            <button wire:click="openCreateForm" class="inline-flex items-center justify-center px-3.5 py-2 text-xs font-semibold text-sky-700 bg-sky-100 hover:bg-sky-200 dark:bg-sky-950/40 dark:text-sky-400 border border-sky-200/40 dark:border-sky-900/40 rounded-xl shadow-sm hover:-translate-y-0.5 transition-all duration-200">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Add Website
            </button>
        @endif
    </div>

    <!-- Inline Form Card (Add/Edit) -->
    @if($showForm)
        <div class="bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-5 shadow-inner space-y-5">
            <h4 class="font-semibold text-sm text-slate-800 dark:text-white flex items-center">
                <span class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></span>
                {{ $editingId ? 'Edit Website' : 'Add New Website' }}
            </h4>

            <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Name -->
                <div class="md:col-span-1">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Website Name <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="name" placeholder="e.g. Main Website, Blog, Landing" class="w-full px-3.5 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                    @error('name') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- URL -->
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Website Address (URL) <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="url" placeholder="https://example.com" class="w-full px-3.5 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                    @error('url') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Integration Status <span class="text-rose-500">*</span></label>
                    <select wire:model="status" class="w-full px-3.5 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                        <option value="No integration">No integration</option>
                        <option value="Test">Test</option>
                        <option value="Live">Live</option>
                    </select>
                    @error('status') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- VISA Status -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">VISA Status <span class="text-rose-500">*</span></label>
                    <select wire:model="visa_status" class="w-full px-3.5 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                        <option value="Stopped">Stopped</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Working">Working</option>
                    </select>
                    @error('visa_status') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- MasterCard Status -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">MasterCard Status <span class="text-rose-500">*</span></label>
                    <select wire:model="mastercard_status" class="w-full px-3.5 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                        <option value="Stopped">Stopped</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Working">Working</option>
                    </select>
                    @error('mastercard_status') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Gateway Providers -->
                <div class="md:col-span-2 space-y-2">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Gateway Providers</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 max-h-[180px] overflow-y-auto custom-scroll">
                        @foreach(\App\Livewire\Projects\WebsitesSection::AVAILABLE_GATEWAYS as $gw)
                            <label class="flex items-center space-x-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                <input type="checkbox" wire:model="gateways" value="{{ $gw }}" class="rounded bg-slate-50 dark:bg-slate-950 border-slate-300 dark:border-slate-800 text-indigo-600 focus:ring-indigo-500/20 focus:ring-offset-slate-900">
                                <span>{{ $gw }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('gateways') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Form Buttons -->
                <div class="md:col-span-2 flex items-center justify-end space-x-2 pt-2">
                    <button type="button" wire:click="cancel" class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl transition-all duration-150">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100/80 dark:bg-indigo-950/30 dark:text-indigo-400 border border-indigo-200/40 dark:border-indigo-800/40 rounded-xl transition-all duration-150">
                        Save
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Websites Table -->
    <div class="border border-slate-100 dark:border-slate-800/80 rounded-2xl overflow-hidden bg-white dark:bg-slate-900 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="text-slate-400 dark:text-slate-500 font-semibold uppercase bg-slate-50/50 dark:bg-slate-950/20 border-b border-slate-100 dark:border-slate-800/60">
                        <th class="p-3.5">Name</th>
                        <th class="p-3.5">Link (URL)</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5">VISA Status</th>
                        <th class="p-3.5">MasterCard Status</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40">
                    @forelse($websites as $web)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors duration-150">
                            <!-- Name -->
                            <td class="p-3.5 whitespace-nowrap">
                                <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $web->name }}</div>
                                @if(!empty($web->gateways))
                                    <div class="flex flex-wrap gap-1 mt-1 max-w-[320px]">
                                        @foreach($web->gateways as $gw)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-200/20 dark:border-indigo-900/20">
                                                {{ $gw }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            <!-- URL Link -->
                            <td class="p-3.5 text-slate-500 dark:text-slate-400">
                                <a href="{{ $web->url }}" target="_blank" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 inline-flex items-center space-x-1 font-mono">
                                    <span>{{ $web->url }}</span>
                                    <svg class="h-3 w-3 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </td>

                            <!-- Status Badge -->
                            <td class="p-3.5 whitespace-nowrap">
                                @if(($web->status ?? 'No integration') === 'Live')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200/20">
                                        Live
                                    </span>
                                @elseif(($web->status ?? 'No integration') === 'Test')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/20">
                                        Test
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200/30">
                                        No integration
                                    </span>
                                @endif
                            </td>

                            <!-- VISA Status Badge -->
                            <td class="p-3.5 whitespace-nowrap">
                                @if(($web->visa_status ?? 'Stopped') === 'Working')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200/20">
                                        Working
                                    </span>
                                @elseif(($web->visa_status ?? 'Stopped') === 'In Progress')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-sky-50 text-sky-700 dark:bg-sky-950/30 dark:text-sky-400 border border-sky-200/20">
                                        In Progress
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 border border-rose-200/20">
                                        Stopped
                                    </span>
                                @endif
                            </td>

                            <!-- MasterCard Status Badge -->
                            <td class="p-3.5 whitespace-nowrap">
                                @if(($web->mastercard_status ?? 'Stopped') === 'Working')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200/20">
                                        Working
                                    </span>
                                @elseif(($web->mastercard_status ?? 'Stopped') === 'In Progress')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-sky-50 text-sky-700 dark:bg-sky-950/30 dark:text-sky-400 border border-sky-200/20">
                                        In Progress
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 border border-rose-200/20">
                                        Stopped
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="p-3.5 text-right space-x-1 whitespace-nowrap">
                                <button type="button" wire:click="edit({{ $web->id }})" class="p-1 rounded text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-amber-600 dark:hover:text-amber-400 transition-all duration-150" title="Edit">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <button type="button" wire:click="openTransferModal({{ $web->id }})" class="p-1 rounded text-slate-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/20 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all duration-150" title="Transfer to another company">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </button>
                                <button type="button" wire:click="delete({{ $web->id }})" wire:confirm="Are you sure you want to delete this website? All linked credentials will be unlinked." class="p-1 rounded text-slate-400 hover:bg-rose-50 dark:hover:bg-rose-950/20 hover:text-rose-600 dark:hover:text-rose-400 transition-all duration-150" title="Delete">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                No websites have been added for this project yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Transfer Website Modal --}}
    @if($showTransferModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" wire:click="closeTransferModal"></div>

                <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-800/80 w-full max-w-md overflow-hidden">

                    {{-- Header --}}
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/20 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                                Website Management
                            </span>
                            <h3 class="text-base font-bold text-slate-800 dark:text-white font-outfit mt-0.5">
                                Transfer to Another Company
                            </h3>
                        </div>
                        <button type="button" wire:click="closeTransferModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors cursor-pointer">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Body --}}
                    <form wire:submit.prevent="transferWebsite">
                        <div class="px-6 py-6 space-y-5">

                            {{-- Website being transferred --}}
                            <div class="p-3 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200/60 dark:border-indigo-800/60 rounded-xl flex items-center gap-3">
                                <span class="p-2 bg-indigo-100 dark:bg-indigo-900/40 rounded-lg text-indigo-600 dark:text-indigo-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 dark:text-indigo-400">Website to Transfer</p>
                                    <p class="text-xs font-semibold text-slate-800 dark:text-white mt-0.5">{{ $transferWebsiteName }}</p>
                                </div>
                            </div>

                            {{-- Target Company --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                    Transfer To <span class="text-rose-500">*</span>
                                </label>
                                <select wire:model="transferToProjectId"
                                        class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                                    <option value="">— Select Company —</option>
                                    @foreach($allProjects as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                                @error('transferToProjectId')
                                    <span class="text-[11px] text-rose-500 block mt-1.5">{{ $message }}</span>
                                @enderror
                            </div>

                            <p class="text-[11px] text-slate-400 dark:text-slate-500 leading-relaxed">
                                <i class="fa-solid fa-circle-info text-sky-400 mr-1"></i>
                                The website and its settings will be moved to the selected company. Credentials linked to this website will also be re-associated.
                            </p>
                        </div>

                        {{-- Footer --}}
                        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/20 flex items-center justify-end space-x-2">
                            <button type="button" wire:click="closeTransferModal"
                                    class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl transition-all duration-150 cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl transition-all duration-150 cursor-pointer shadow-sm disabled:opacity-60">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                <span wire:loading.remove wire:target="transferWebsite">Transfer Website</span>
                                <span wire:loading wire:target="transferWebsite">Transferring...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
