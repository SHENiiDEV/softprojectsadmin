<div class="space-y-6 pb-12">
    {{-- Toast Notification --}}
    @if($successMessage)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="fixed bottom-6 right-6 z-50 flex items-center p-4 mb-4 text-emerald-800 rounded-2xl bg-emerald-50 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shadow-xl transition-all duration-300">
            <svg class="w-5 h-5 mr-3 flex-shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <div class="text-sm font-semibold">{{ $successMessage }}</div>
            <button type="button" @click="show = false" class="ml-4 -mx-1.5 -my-1.5 text-emerald-500 hover:text-emerald-700 p-1.5">
                <span class="sr-only">Close</span>
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </button>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div>
            <div class="flex items-center space-x-3">
                <div class="p-2.5 rounded-xl bg-gradient-to-tr from-sky-500 to-indigo-600 text-white shadow-md shadow-sky-500/20">
                    <i class="fa-solid fa-scale-balanced text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">
                        Acquiring & Banking Tariffs
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Provider fee conditions, matrix comparison & transaction cost simulator (Smart Routing)
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <button wire:click="setTab('simulator')"
                    class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:hover:bg-indigo-900/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 transition-all shadow-sm">
                <i class="fa-solid fa-calculator mr-2 text-indigo-500"></i>
                Cost Simulator
            </button>

            <button wire:click="openCreateProviderModal"
                    class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-xl bg-sky-600 hover:bg-sky-500 text-white transition-all shadow-md shadow-sky-500/20">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Provider
            </button>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Providers</p>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-2xl font-black text-slate-800 dark:text-white">{{ $totalProviders }}</span>
                    <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-2 py-0.5 rounded-md">
                        {{ $activeProviders }} active
                    </span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-building-columns"></i>
            </div>
        </div>

        <div class="p-5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Acquiring & EMIs</p>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-2xl font-black text-slate-800 dark:text-white">{{ $acquiringCount }}</span>
                    <span class="text-xs text-slate-500">/ {{ $emiCount }} EMI</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-credit-card"></i>
            </div>
        </div>

        <div class="p-5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Best EU Card Rate</p>
                <div class="mt-1">
                    @if($lowestCardRate)
                        <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                            {{ $lowestCardRate->formatted_rate }}
                        </span>
                        <p class="text-[11px] text-slate-400 truncate max-w-[130px]">{{ $lowestCardRate->provider?->name }}</p>
                    @else
                        <span class="text-sm text-slate-400">N/A</span>
                    @endif
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-tag"></i>
            </div>
        </div>

        <div class="p-5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Best SEPA Rate</p>
                <div class="mt-1">
                    @if($lowestSepaRate)
                        <span class="text-2xl font-black text-teal-600 dark:text-teal-400">
                            {{ $lowestSepaRate->formatted_rate }}
                        </span>
                        <p class="text-[11px] text-slate-400 truncate max-w-[130px]">{{ $lowestSepaRate->provider?->name }}</p>
                    @else
                        <span class="text-sm text-slate-400">N/A</span>
                    @endif
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-money-bill-transfer"></i>
            </div>
        </div>
    </div>

    {{-- Clean Modern Segmented Tab Navigation --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-1.5">
        <nav class="flex flex-wrap items-center gap-1">
            <button wire:click="setTab('cards')"
                    class="flex items-center px-4 py-2.5 text-xs font-semibold rounded-xl transition-all {{ $activeTab === 'cards' ? 'bg-sky-500 text-white shadow-md shadow-sky-500/20 font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                <i class="fa-solid fa-credit-card mr-2 text-sm"></i>
                Card Acquiring
            </button>

            <button wire:click="setTab('banking')"
                    class="flex items-center px-4 py-2.5 text-xs font-semibold rounded-xl transition-all {{ $activeTab === 'banking' ? 'bg-sky-500 text-white shadow-md shadow-sky-500/20 font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                <i class="fa-solid fa-building-columns mr-2 text-sm"></i>
                Banking (SEPA & SWIFT)
            </button>

            <button wire:click="setTab('fees')"
                    class="flex items-center px-4 py-2.5 text-xs font-semibold rounded-xl transition-all {{ $activeTab === 'fees' ? 'bg-sky-500 text-white shadow-md shadow-sky-500/20 font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                <i class="fa-solid fa-receipt mr-2 text-sm"></i>
                Setup & Maintenance Fees
            </button>

            <button wire:click="setTab('simulator')"
                    class="flex items-center px-4 py-2.5 text-xs font-semibold rounded-xl transition-all {{ $activeTab === 'simulator' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-bold' : 'text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50' }}">
                <i class="fa-solid fa-calculator mr-2 text-sm"></i>
                Cost Simulator
            </button>

            <button wire:click="setTab('providers')"
                    class="flex items-center px-4 py-2.5 text-xs font-semibold rounded-xl transition-all {{ $activeTab === 'providers' ? 'bg-slate-800 text-white dark:bg-slate-700 font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                <i class="fa-solid fa-sliders mr-2 text-sm"></i>
                Providers Directory
            </button>
        </nav>
    </div>

    {{-- Global Filters Bar --}}
    @if(in_array($activeTab, ['cards', 'banking', 'fees', 'providers']))
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="relative w-full md:w-80">
            <svg class="absolute left-3.5 top-3 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search provider, code, fee or notes..."
                   class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 dark:text-white placeholder-slate-400" />
        </div>

        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-500">Type:</span>
                <select wire:model.live="filterType"
                        class="px-3 py-1.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-sky-500">
                    <option value="all">All Types</option>
                    <option value="acquiring">Acquiring</option>
                    <option value="emi">EMI / Banking</option>
                    <option value="hybrid">Hybrid (Acq + EMI)</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-500">Currency:</span>
                <select wire:model.live="filterCurrency"
                        class="px-3 py-1.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-sky-500">
                    <option value="all">All Currencies</option>
                    <option value="EUR">EUR (€)</option>
                    <option value="USD">USD ($)</option>
                    <option value="GBP">GBP (£)</option>
                </select>
            </div>

            <label class="flex items-center space-x-2 text-xs font-medium text-slate-600 dark:text-slate-400 cursor-pointer ml-2">
                <input type="checkbox" wire:model.live="onlyActive" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500 dark:bg-slate-800" />
                <span>Active only</span>
            </label>
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- TAB 1: CARD ACQUIRING MATRIX --}}
    {{-- ========================================================================= --}}
    @if($activeTab === 'cards')
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-800 dark:text-white">Card Acquiring Comparison Matrix</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Processing rates across EU, Non-EU, Corporate cards, FX markup & Rolling Reserve</p>
            </div>
            <button wire:click="openCreateRateModal()" class="text-xs font-semibold text-sky-600 hover:text-sky-700 dark:text-sky-400 flex items-center">
                <i class="fa-solid fa-plus mr-1.5"></i> Add Rate
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/75 dark:bg-slate-950/50 text-slate-500 dark:text-slate-400 text-[11px] uppercase font-bold border-b border-slate-200/80 dark:border-slate-800">
                    <tr>
                        <th class="py-3.5 px-5">Provider</th>
                        <th class="py-3.5 px-4">EU Cards (Visa / MC)</th>
                        <th class="py-3.5 px-4">Non-EU / International</th>
                        <th class="py-3.5 px-4">Corporate / Premium</th>
                        <th class="py-3.5 px-4">FX Margin</th>
                        <th class="py-3.5 px-4">Chargeback Fee</th>
                        <th class="py-3.5 px-4">Rolling Reserve</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60 dark:divide-slate-800">
                    @forelse($providers as $provider)
                        @php
                            $euRate = $provider->processingRates->where('payment_method', 'card')->where('card_region', 'eu')->where('is_active', true)->first();
                            $nonEuRate = $provider->processingRates->where('payment_method', 'card')->whereIn('card_region', ['non_eu', 'international'])->where('is_active', true)->first();
                            $corpRate = $provider->processingRates->where('payment_method', 'card')->where('card_region', 'corporate')->where('is_active', true)->first();
                            $fxFee = $provider->serviceFees->whereIn('fee_code', ['fx_margin', 'fx_main'])->first();
                            $cbFee = $provider->serviceFees->where('fee_code', 'chargeback')->first();
                            $reserve = $provider->reserve;
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            {{-- Provider Info --}}
                            <td class="py-4 px-5">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-sky-400 to-indigo-600 text-white font-bold flex items-center justify-center text-xs shadow-sm">
                                        {{ strtoupper(substr($provider->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                            {{ $provider->name }}
                                            @if(! $provider->is_active)
                                                <span class="px-2 py-0.5 text-[10px] font-medium bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400 rounded-full">Inactive</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-400 flex items-center gap-1.5">
                                            <span class="uppercase tracking-wider text-[10px] font-semibold text-sky-600 dark:text-sky-400">{{ $provider->type }}</span>
                                            <span>•</span>
                                            <span>{{ $provider->base_currency }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- EU Cards --}}
                            <td class="py-4 px-4">
                                @if($euRate)
                                    <div class="inline-flex flex-col">
                                        <span class="font-bold text-slate-800 dark:text-white text-xs bg-sky-50 dark:bg-sky-950/50 text-sky-700 dark:text-sky-300 px-2.5 py-1 rounded-lg border border-sky-100 dark:border-sky-900/50">
                                            {{ $euRate->formatted_rate }}
                                        </span>
                                        @if($euRate->notes)
                                            <span class="text-[10px] text-slate-400 mt-0.5">{{ $euRate->notes }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">—</span>
                                @endif
                            </td>

                            {{-- Non-EU Cards --}}
                            <td class="py-4 px-4">
                                @if($nonEuRate)
                                    <div class="inline-flex flex-col">
                                        <span class="font-bold text-slate-800 dark:text-white text-xs bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 px-2.5 py-1 rounded-lg border border-amber-100 dark:border-amber-900/50">
                                            {{ $nonEuRate->formatted_rate }}
                                        </span>
                                        @if($nonEuRate->notes)
                                            <span class="text-[10px] text-slate-400 mt-0.5">{{ $nonEuRate->notes }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">—</span>
                                @endif
                            </td>

                            {{-- Corporate Cards --}}
                            <td class="py-4 px-4">
                                @if($corpRate)
                                    <div class="inline-flex flex-col">
                                        <span class="font-bold text-slate-800 dark:text-white text-xs bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 px-2.5 py-1 rounded-lg border border-purple-100 dark:border-purple-900/50">
                                            {{ $corpRate->formatted_rate }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">—</span>
                                @endif
                            </td>

                            {{-- FX Margin --}}
                            <td class="py-4 px-4">
                                @if($fxFee)
                                    <span class="font-semibold text-slate-700 dark:text-slate-200 text-xs">
                                        {{ $fxFee->formatted_fee }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 italic">—</span>
                                @endif
                            </td>

                            {{-- Chargeback Fee --}}
                            <td class="py-4 px-4">
                                @if($cbFee)
                                    <span class="font-semibold text-rose-600 dark:text-rose-400 text-xs">
                                        {{ $cbFee->formatted_fee }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 italic">—</span>
                                @endif
                            </td>

                            {{-- Rolling Reserve --}}
                            <td class="py-4 px-4">
                                @if($reserve)
                                    <div class="flex flex-col">
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400 text-xs">
                                            {{ $reserve->formatted_summary }}
                                        </span>
                                        @if($reserve->conditions)
                                            <span class="text-[10px] text-slate-400 line-clamp-1" title="{{ $reserve->conditions }}">
                                                {{ $reserve->conditions }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">No reserve</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-4 px-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <button wire:click="openEditReserveModal({{ $provider->id }})" title="Configure Rolling Reserve" class="p-1.5 text-slate-400 hover:text-emerald-600 transition-colors">
                                        <i class="fa-solid fa-shield-halved text-xs"></i>
                                    </button>
                                    <button wire:click="openCreateRateModal({{ $provider->id }})" title="Add rate" class="p-1.5 text-slate-400 hover:text-sky-600 transition-colors">
                                        <i class="fa-solid fa-plus text-xs"></i>
                                    </button>
                                    <button wire:click="openEditProviderModal({{ $provider->id }})" title="Edit provider" class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                No providers matching selected filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- TAB 2: BANKING (SEPA / SWIFT / A2A) --}}
    {{-- ========================================================================= --}}
    @if($activeTab === 'banking')
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-800 dark:text-white">Banking Transfers & Accounts (SEPA / SWIFT / Internal)</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Compare incoming and outgoing SEPA tiers, B2B, B2C, internal book transfers and SWIFT wires</p>
            </div>
            <button wire:click="openCreateRateModal()" class="text-xs font-semibold text-sky-600 hover:text-sky-700 dark:text-sky-400 flex items-center">
                <i class="fa-solid fa-plus mr-1.5"></i> Add Banking Rate
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/75 dark:bg-slate-950/50 text-slate-500 dark:text-slate-400 text-[11px] uppercase font-bold border-b border-slate-200/80 dark:border-slate-800">
                    <tr>
                        <th class="py-3.5 px-5">Provider</th>
                        <th class="py-3.5 px-4">Incoming SEPA (Volume Tiers)</th>
                        <th class="py-3.5 px-4">Outgoing SEPA B2C</th>
                        <th class="py-3.5 px-4">Outgoing SEPA B2B</th>
                        <th class="py-3.5 px-4">Internal Transfers (Book)</th>
                        <th class="py-3.5 px-4">SWIFT In / Out</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60 dark:divide-slate-800">
                    @forelse($providers as $provider)
                        @php
                            $sepaPayins = $provider->processingRates->where('payment_method', 'sepa')->where('flow_type', 'payin')->where('is_active', true);
                            $sepaPayoutB2c = $provider->processingRates->where('payment_method', 'sepa')->where('flow_type', 'payout')->where('target_audience', 'b2c')->where('is_active', true)->first();
                            $sepaPayoutB2b = $provider->processingRates->where('payment_method', 'sepa')->where('flow_type', 'payout')->where('target_audience', 'b2b')->where('is_active', true)->first();
                            $internalRate = $provider->processingRates->where('payment_method', 'internal')->where('is_active', true)->first();
                            $swiftIn = $provider->processingRates->where('payment_method', 'swift')->where('flow_type', 'payin')->where('is_active', true)->first();
                            $swiftOut = $provider->processingRates->where('payment_method', 'swift')->where('flow_type', 'payout')->where('is_active', true)->first();
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            {{-- Provider --}}
                            <td class="py-4 px-5">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-teal-400 to-emerald-600 text-white font-bold flex items-center justify-center text-xs shadow-sm">
                                        {{ strtoupper(substr($provider->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800 dark:text-white">{{ $provider->name }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $provider->base_currency }} • {{ $provider->type }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Incoming SEPA Tiers --}}
                            <td class="py-4 px-4">
                                @if($sepaPayins->isNotEmpty())
                                    <div class="space-y-1">
                                        @foreach($sepaPayins as $tier)
                                            <div class="flex items-center space-x-2 text-xs">
                                                <span class="font-semibold text-slate-500 dark:text-slate-400 text-[11px] w-28">{{ $tier->target_audience !== 'all' ? strtoupper($tier->target_audience).': ' : '' }}{{ $tier->tier_label }}:</span>
                                                <span class="font-bold text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/50 px-2 py-0.5 rounded border border-teal-100 dark:border-teal-900/50">
                                                    {{ $tier->formatted_rate }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">—</span>
                                @endif
                            </td>

                            {{-- Outgoing SEPA B2C --}}
                            <td class="py-4 px-4">
                                @if($sepaPayoutB2c)
                                    <span class="font-bold text-slate-800 dark:text-white text-xs bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-lg">
                                        {{ $sepaPayoutB2c->formatted_rate }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 italic">—</span>
                                @endif
                            </td>

                            {{-- Outgoing SEPA B2B --}}
                            <td class="py-4 px-4">
                                @if($sepaPayoutB2b)
                                    <span class="font-bold text-slate-800 dark:text-white text-xs bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-lg border border-indigo-100 dark:border-indigo-900/50">
                                        {{ $sepaPayoutB2b->formatted_rate }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 italic">—</span>
                                @endif
                            </td>

                            {{-- Internal Transfers --}}
                            <td class="py-4 px-4">
                                @if($internalRate)
                                    <span class="font-semibold text-slate-700 dark:text-slate-200 text-xs">
                                        {{ $internalRate->formatted_rate }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 italic">—</span>
                                @endif
                            </td>

                            {{-- SWIFT --}}
                            <td class="py-4 px-4">
                                <div class="space-y-1 text-xs">
                                    @if($swiftIn)
                                        <div><span class="text-slate-400">In:</span> <span class="font-medium text-slate-700 dark:text-slate-200">{{ $swiftIn->formatted_rate }}</span></div>
                                    @endif
                                    @if($swiftOut)
                                        <div><span class="text-slate-400">Out:</span> <span class="font-medium text-slate-700 dark:text-slate-200">{{ $swiftOut->formatted_rate }}</span></div>
                                    @endif
                                    @if(! $swiftIn && ! $swiftOut)
                                        <span class="text-slate-400 italic">—</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="py-4 px-4 text-right">
                                <button wire:click="openCreateRateModal({{ $provider->id }})" title="Add rate" class="p-1.5 text-slate-400 hover:text-sky-600 transition-colors">
                                    <i class="fa-solid fa-plus text-xs"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400">
                                No banking rates available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- TAB 3: SETUP & MAINTENANCE FEES --}}
    {{-- ========================================================================= --}}
    @if($activeTab === 'fees')
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-800 dark:text-white">Setup, Maintenance & Ancillary Service Fees</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Onboarding, API integration, monthly account/IBAN maintenance, and administrative bank services</p>
            </div>
            <button wire:click="openCreateFeeModal()" class="text-xs font-semibold text-sky-600 hover:text-sky-700 dark:text-sky-400 flex items-center">
                <i class="fa-solid fa-plus mr-1.5"></i> Add Service Fee
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/75 dark:bg-slate-950/50 text-slate-500 dark:text-slate-400 text-[11px] uppercase font-bold border-b border-slate-200/80 dark:border-slate-800">
                    <tr>
                        <th class="py-3.5 px-5">Provider</th>
                        <th class="py-3.5 px-4">Account Opening / Onboarding</th>
                        <th class="py-3.5 px-4">API Integration & Support</th>
                        <th class="py-3.5 px-4">Monthly Maintenance</th>
                        <th class="py-3.5 px-4">Monthly IBAN Fee</th>
                        <th class="py-3.5 px-4">Additional / Ancillary Services</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60 dark:divide-slate-800">
                    @forelse($providers as $provider)
                        @php
                            $onboardingFee = $provider->serviceFees->where('fee_code', 'onboarding')->first();
                            $apiFee = $provider->serviceFees->where('fee_code', 'api_integration')->first();
                            $monthlyFee = $provider->serviceFees->where('fee_code', 'monthly_maintenance')->first();
                            $ibanFee = $provider->serviceFees->where('fee_code', 'monthly_iban')->first();
                            $otherFees = $provider->serviceFees->whereNotIn('fee_code', ['onboarding', 'api_integration', 'monthly_maintenance', 'monthly_iban', 'chargeback', 'fx_margin', 'fx_main']);
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-5">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-amber-400 to-orange-500 text-white font-bold flex items-center justify-center text-xs shadow-sm">
                                        {{ strtoupper(substr($provider->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800 dark:text-white">{{ $provider->name }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $provider->type }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Onboarding --}}
                            <td class="py-4 px-4">
                                @if($onboardingFee)
                                    <span class="font-bold text-slate-800 dark:text-white text-xs bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-lg">
                                        {{ $onboardingFee->formatted_fee }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 italic">Free / None</span>
                                @endif
                            </td>

                            {{-- API Integration --}}
                            <td class="py-4 px-4">
                                @if($apiFee)
                                    <span class="font-bold text-slate-800 dark:text-white text-xs bg-sky-50 dark:bg-sky-950/50 text-sky-700 dark:text-sky-300 px-2.5 py-1 rounded-lg border border-sky-100 dark:border-sky-900/50">
                                        {{ $apiFee->formatted_fee }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 italic">—</span>
                                @endif
                            </td>

                            {{-- Monthly Maintenance --}}
                            <td class="py-4 px-4">
                                @if($monthlyFee)
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400 text-xs">
                                        {{ $monthlyFee->formatted_fee }} / mo
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 italic">€0 / mo</span>
                                @endif
                            </td>

                            {{-- IBAN Fee --}}
                            <td class="py-4 px-4">
                                @if($ibanFee)
                                    <span class="font-medium text-slate-700 dark:text-slate-300 text-xs">
                                        {{ $ibanFee->formatted_fee }} / mo
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 italic">—</span>
                                @endif
                            </td>

                            {{-- Other Additional Fees --}}
                            <td class="py-4 px-4">
                                @if($otherFees->isNotEmpty())
                                    <div class="space-y-1">
                                        @foreach($otherFees->take(3) as $of)
                                            <div class="text-[11px]">
                                                <span class="text-slate-500 dark:text-slate-400">{{ $of->title }}:</span>
                                                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $of->formatted_fee }}</span>
                                            </div>
                                        @endforeach
                                        @if($otherFees->count() > 3)
                                            <div class="text-[10px] text-slate-400 italic">+ {{ $otherFees->count() - 3 }} more services</div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">—</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-4 px-4 text-right">
                                <button wire:click="openCreateFeeModal({{ $provider->id }})" title="Add fee" class="p-1.5 text-slate-400 hover:text-sky-600 transition-colors">
                                    <i class="fa-solid fa-plus text-xs"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400">
                                No service fees found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- TAB 4: COST SIMULATOR / SMART ROUTING --}}
    {{-- ========================================================================= --}}
    @if($activeTab === 'simulator')
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- Simulation Controls Panel --}}
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-5">
            <div class="border-b border-slate-200/80 dark:border-slate-800 pb-4">
                <div class="flex items-center space-x-2 text-indigo-600 dark:text-indigo-400 mb-1">
                    <i class="fa-solid fa-calculator text-lg"></i>
                    <h2 class="text-base font-bold text-slate-800 dark:text-white">Transaction Parameters</h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">Configure transaction amount, method, and region to simulate provider costs & margin</p>
            </div>

            {{-- Amount Input --}}
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                    Transaction Amount (€ / $ / £)
                </label>
                <div class="relative">
                    <span class="absolute left-3.5 top-2.5 text-slate-400 font-bold">€</span>
                    <input type="number" step="1" min="1"
                           wire:model.live.debounce.250ms="simAmount"
                           class="w-full pl-8 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-lg text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500" />
                </div>
                <div class="flex items-center gap-1.5 mt-2">
                    @foreach([50, 100, 500, 1000, 5000, 10000] as $preset)
                        <button type="button" wire:click="$set('simAmount', {{ $preset }})"
                                class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 text-slate-600 dark:text-slate-300 hover:text-indigo-600 transition-colors">
                            €{{ $preset }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Payment Method --}}
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                    Payment Method
                </label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach(['card' => '💳 Cards (Card)', 'sepa' => '🏦 SEPA Transfer', 'swift' => '🌐 SWIFT Wire', 'internal' => '🔄 Internal Book'] as $k => $label)
                        <button type="button" wire:click="$set('simMethod', '{{ $k }}')"
                                class="px-3 py-2 text-xs font-semibold rounded-xl border text-left transition-all {{ $simMethod === $k ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 shadow-sm' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Card Region (If card) --}}
            @if($simMethod === 'card')
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                    Card Geo Region
                </label>
                <select wire:model.live="simRegion"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="eu">🇪🇺 EU / EEA Cards (Interchange++)</option>
                    <option value="non_eu">🌍 Non-EU / International Cards</option>
                    <option value="corporate">🏢 Corporate / Commercial Cards</option>
                    <option value="international">🌐 Global Worldwide Cards</option>
                </select>
            </div>
            @endif

            {{-- Flow Type & Audience --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Flow Type
                    </label>
                    <select wire:model.live="simFlow"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="payin">📥 Pay-in (Incoming)</option>
                        <option value="payout">📤 Pay-out (Payout)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Audience
                    </label>
                    <select wire:model.live="simAudience"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="all">All</option>
                        <option value="b2b">B2B</option>
                        <option value="b2c">B2C</option>
                        <option value="c2b">C2B</option>
                    </select>
                </div>
            </div>

            {{-- Monthly Volume (Tiers) --}}
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                    Monthly Accumulated Volume (Tier)
                </label>
                <div class="relative">
                    <input type="number" step="50000" min="0"
                           wire:model.live.debounce.300ms="simMonthlyVolume"
                           placeholder="0.00"
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500" />
                </div>
                <div class="flex items-center gap-1.5 mt-1.5">
                    <button type="button" wire:click="$set('simMonthlyVolume', 0)" class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-500 rounded">0</button>
                    <button type="button" wire:click="$set('simMonthlyVolume', 500000)" class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-500 rounded">€500k</button>
                    <button type="button" wire:click="$set('simMonthlyVolume', 2500000)" class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-500 rounded">€2.5M</button>
                    <button type="button" wire:click="$set('simMonthlyVolume', 6000000)" class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-500 rounded">€6M+</button>
                </div>
            </div>

            {{-- Calculation Formula Info --}}
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 text-[11px] text-slate-500 dark:text-slate-400">
                <div class="font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center">
                    <i class="fa-solid fa-square-root-variable mr-1.5 text-indigo-500"></i> Fee Formula:
                </div>
                <code>Fee = max(Amount × % + Fixed, Min_Fee)</code>
            </div>
        </div>

        {{-- Simulation Results Panel --}}
        <div class="lg:col-span-8 space-y-6">
            @php $results = $this->simulationResults; @endphp

            @if(count($results) > 0)
                @php
                    $best = $results[0];
                    $secondBest = $results[1] ?? null;
                    $diffSavings = $secondBest ? round($secondBest['fee'] - $best['fee'], 2) : 0;
                @endphp

                {{-- Hero Highlight: Optimal / Cheapest Route --}}
                <div class="p-6 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-2xl text-white shadow-lg shadow-emerald-500/15 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 backdrop-blur-sm text-xs font-bold uppercase tracking-wider">
                            <i class="fa-solid fa-crown text-amber-300"></i> Best Route / Optimal Cost
                        </div>
                        <h3 class="text-2xl font-black tracking-tight">{{ $best['provider']->name }}</h3>
                        <p class="text-xs text-emerald-100">
                            Provider fee: <strong class="text-white text-sm">€{{ number_format($best['fee'], 2) }}</strong> (effective rate <strong>{{ $best['effective_percent'] }}%</strong>)
                            @if($diffSavings > 0)
                                • Save <strong>€{{ number_format($diffSavings, 2) }}</strong> per transaction vs 2nd route
                            @endif
                        </p>
                    </div>

                    <div class="text-right bg-white/10 backdrop-blur-sm p-4 rounded-xl border border-white/20 min-w-[200px]">
                        <div class="text-[11px] uppercase tracking-wider text-emerald-100 font-semibold">Net Settlement Amount</div>
                        <div class="text-2xl font-black text-white">€{{ number_format($best['settlement_amount'], 2) }}</div>
                        @if($best['reserve_amount'] > 0)
                            <div class="text-[10px] text-emerald-200">Rolling Reserve ({{ $best['reserve_percent'] }}%): €{{ number_format($best['reserve_amount'], 2) }}</div>
                        @endif
                    </div>
                </div>

                {{-- Detailed Comparative Ranking Table --}}
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                        <h3 class="font-bold text-slate-800 dark:text-white text-base">
                            Ranked Provider Comparison for €{{ number_format($simAmount, 2) }}
                        </h3>
                        <span class="text-xs font-medium text-slate-400">Sorted by lowest cost</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50/75 dark:bg-slate-950/50 text-slate-500 dark:text-slate-400 text-[11px] uppercase font-bold border-b border-slate-200/80 dark:border-slate-800">
                                <tr>
                                    <th class="py-3.5 px-4 w-12 text-center">Rank</th>
                                    <th class="py-3.5 px-4">Provider</th>
                                    <th class="py-3.5 px-4">Applied Rate Rule</th>
                                    <th class="py-3.5 px-4 text-right">Fee (€)</th>
                                    <th class="py-3.5 px-4 text-right">Eff. %</th>
                                    <th class="py-3.5 px-4 text-right">Rolling Reserve</th>
                                    <th class="py-3.5 px-4 text-right">Net Settlement</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/60 dark:divide-slate-800">
                                @foreach($results as $index => $item)
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors {{ $index === 0 ? 'bg-emerald-50/30 dark:bg-emerald-950/10' : '' }}">
                                        {{-- Rank --}}
                                        <td class="py-4 px-4 text-center">
                                            @if($index === 0)
                                                <span class="w-6 h-6 rounded-full bg-emerald-500 text-white font-bold text-xs inline-flex items-center justify-center shadow-sm">1</span>
                                            @elseif($index === 1)
                                                <span class="w-6 h-6 rounded-full bg-slate-300 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs inline-flex items-center justify-center">2</span>
                                            @else
                                                <span class="text-xs text-slate-400 font-semibold">{{ $index + 1 }}</span>
                                            @endif
                                        </td>

                                        {{-- Provider --}}
                                        <td class="py-4 px-4">
                                            <div class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                                {{ $item['provider']->name }}
                                                @if($index === 0)
                                                    <span class="px-2 py-0.5 text-[9px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 rounded-md">CHEAPEST</span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] text-slate-400">{{ $item['provider']->type }}</div>
                                        </td>

                                        {{-- Applied Rate --}}
                                        <td class="py-4 px-4">
                                            @if($item['rate'])
                                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-lg">
                                                    {{ $item['rate']->formatted_rate }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Fee Amount --}}
                                        <td class="py-4 px-4 text-right">
                                            <span class="font-black text-sm {{ $index === 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-800 dark:text-white' }}">
                                                €{{ number_format($item['fee'], 2) }}
                                            </span>
                                        </td>

                                        {{-- Effective % --}}
                                        <td class="py-4 px-4 text-right">
                                            <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">
                                                {{ $item['effective_percent'] }}%
                                            </span>
                                        </td>

                                        {{-- Reserve --}}
                                        <td class="py-4 px-4 text-right">
                                            @if($item['reserve_amount'] > 0)
                                                <span class="text-xs font-medium text-amber-600 dark:text-amber-400">
                                                    €{{ number_format($item['reserve_amount'], 2) }} ({{ $item['reserve_percent'] }}%)
                                                </span>
                                            @else
                                                <span class="text-xs text-slate-400">—</span>
                                            @endif
                                        </td>

                                        {{-- Net Settlement --}}
                                        <td class="py-4 px-4 text-right">
                                            <span class="font-black text-sm text-slate-900 dark:text-slate-100">
                                                €{{ number_format($item['settlement_amount'], 2) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-slate-900 p-12 rounded-2xl border border-slate-200/80 dark:border-slate-800 text-center text-slate-400">
                    <i class="fa-solid fa-circle-exclamation text-3xl mb-3 text-slate-300"></i>
                    <p class="font-semibold">No rates match the selected simulation parameters.</p>
                    <p class="text-xs mt-1">Try selecting another payment method or card region.</p>
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- TAB 5: PROVIDERS DIRECTORY & CRUD --}}
    {{-- ========================================================================= --}}
    @if($activeTab === 'providers')
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($providers as $p)
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-5 flex flex-col justify-between space-y-4">
                    <div>
                        {{-- Top line --}}
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-sky-500 to-indigo-600 text-white font-black flex items-center justify-center text-sm shadow-md shadow-sky-500/20">
                                    {{ strtoupper(substr($p->name, 0, 2)) }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-800 dark:text-white text-base">{{ $p->name }}</h3>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-sky-50 dark:bg-sky-950 text-sky-600 dark:text-sky-400 border border-sky-100 dark:border-sky-900">
                                            {{ strtoupper($p->type) }}
                                        </span>
                                        <span class="text-xs text-slate-400">{{ $p->base_currency }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Active Toggle Button --}}
                            <button wire:click="toggleProviderStatus({{ $p->id }})"
                                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out {{ $p->is_active ? 'bg-sky-600' : 'bg-slate-300 dark:bg-slate-700' }}">
                                <span class="sr-only">Toggle active</span>
                                <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $p->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                            </button>
                        </div>

                        {{-- Details --}}
                        <div class="mt-4 space-y-2 text-xs text-slate-600 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800/80 pt-3">
                            @if($p->contact_person || $p->contact_email)
                                <div class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-user text-slate-400 w-3.5"></i>
                                    <span>{{ $p->contact_person ?? 'Contact' }} ({{ $p->contact_email ?? '—' }})</span>
                                </div>
                            @endif

                            @if($p->account_manager)
                                <div class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-briefcase text-slate-400 w-3.5"></i>
                                    <span>Manager: <strong>{{ $p->account_manager }}</strong></span>
                                </div>
                            @endif

                            @if($p->website)
                                <div class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-globe text-slate-400 w-3.5"></i>
                                    <a href="{{ $p->website }}" target="_blank" class="text-sky-600 hover:underline truncate max-w-[200px]">{{ $p->website }}</a>
                                </div>
                            @endif

                            @if($p->notes)
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 bg-slate-50 dark:bg-slate-800/50 p-2 rounded-xl italic">
                                    {{ $p->notes }}
                                </p>
                            @endif
                        </div>

                        {{-- Counts --}}
                        <div class="grid grid-cols-3 gap-2 mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 text-center">
                            <div class="bg-slate-50 dark:bg-slate-800/60 p-2 rounded-xl">
                                <span class="block text-xs font-bold text-slate-800 dark:text-white">{{ $p->processingRates->count() }}</span>
                                <span class="text-[10px] text-slate-400">Rates</span>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-800/60 p-2 rounded-xl">
                                <span class="block text-xs font-bold text-slate-800 dark:text-white">{{ $p->serviceFees->count() }}</span>
                                <span class="text-[10px] text-slate-400">Fees</span>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-800/60 p-2 rounded-xl">
                                <span class="block text-xs font-bold text-slate-800 dark:text-white">{{ $p->reserve ? $p->reserve->rate_percent.'%' : '0%' }}</span>
                                <span class="text-[10px] text-slate-400">Reserve</span>
                            </div>
                        </div>
                    </div>

                    {{-- Card Footer Actions --}}
                    <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex items-center space-x-1">
                            <button wire:click="openCreateRateModal({{ $p->id }})" title="Add rate" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 hover:bg-sky-100">
                                + Rate
                            </button>
                            <button wire:click="openCreateFeeModal({{ $p->id }})" title="Add fee" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 hover:bg-amber-100">
                                + Fee
                            </button>
                        </div>

                        <div class="flex items-center space-x-1.5">
                            <button wire:click="openEditProviderModal({{ $p->id }})" class="p-1.5 text-slate-400 hover:text-slate-800 dark:hover:text-white">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button wire:confirm="Are you sure you want to delete provider {{ $p->name }} and all associated rates?"
                                    wire:click="deleteProvider({{ $p->id }})" class="p-1.5 text-slate-400 hover:text-rose-600">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-slate-400 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
                    No providers found. Click "Add Provider" to create your first partner.
                </div>
            @endforelse
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- MODALS --}}
    {{-- ========================================================================= --}}

    {{-- 1. Provider Modal --}}
    @if($showProviderModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-800 dark:text-white">
                    {{ $editingProviderId ? 'Edit Provider' : 'Add New Provider' }}
                </h3>
                <button wire:click="$set('showProviderModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form wire:submit.prevent="saveProvider" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Provider Name *</label>
                    <input type="text" wire:model="providerForm.name" placeholder="e.g. GuruPay, Payally"
                           class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white" />
                    @error('providerForm.name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Unique Code (Slug) *</label>
                        <input type="text" wire:model="providerForm.code" placeholder="gurupay"
                               class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white" />
                        @error('providerForm.code') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Type *</label>
                        <select wire:model="providerForm.type"
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white">
                            <option value="hybrid">Hybrid (Acquiring + EMI)</option>
                            <option value="acquiring">Acquiring Only</option>
                            <option value="emi">EMI / Banking Only</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Base Currency</label>
                        <select wire:model="providerForm.base_currency"
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white">
                            <option value="EUR">EUR (€)</option>
                            <option value="USD">USD ($)</option>
                            <option value="GBP">GBP (£)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Website URL</label>
                        <input type="text" wire:model="providerForm.website" placeholder="https://..."
                               class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Contact Person</label>
                        <input type="text" wire:model="providerForm.contact_person" placeholder="First Last name"
                               class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Contact Email</label>
                        <input type="email" wire:model="providerForm.contact_email" placeholder="support@..."
                               class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Internal Account Manager</label>
                    <input type="text" wire:model="providerForm.account_manager" placeholder="e.g. Kevin, Elena"
                           class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white" />
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Notes / Compliance specifics</label>
                    <textarea wire:model="providerForm.notes" rows="2" placeholder="Due diligence rules, supported geographics..."
                              class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" wire:click="$set('showProviderModal', false)"
                            class="px-4 py-2 text-sm font-semibold rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2 text-sm font-semibold rounded-xl bg-sky-600 hover:bg-sky-500 text-white shadow-md shadow-sky-600/20">
                        Save Provider
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- 2. Processing Rate Modal --}}
    @if($showRateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-800 dark:text-white">
                    {{ $editingRateId ? 'Edit Processing Rate' : 'Add Processing Rate' }}
                </h3>
                <button wire:click="$set('showRateModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form wire:submit.prevent="saveRate" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Provider *</label>
                    <select wire:model="rateForm.provider_id"
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white">
                        @foreach($allProvidersList as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Payment Method *</label>
                        <select wire:model="rateForm.payment_method"
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white">
                            <option value="card">Card (Visa/Mastercard)</option>
                            <option value="sepa">SEPA</option>
                            <option value="swift">SWIFT</option>
                            <option value="internal">Internal (Book-to-Book)</option>
                            <option value="crypto">Crypto</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Flow Type *</label>
                        <select wire:model="rateForm.flow_type"
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white">
                            <option value="payin">Pay-in (Incoming)</option>
                            <option value="payout">Pay-out (Payout)</option>
                            <option value="refund">Refund</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Card Region</label>
                        <select wire:model="rateForm.card_region"
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white">
                            <option value="all">All Regions</option>
                            <option value="eu">EU / EEA</option>
                            <option value="non_eu">Non-EU</option>
                            <option value="international">International</option>
                            <option value="corporate">Corporate / Premium</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Audience</label>
                        <select wire:model="rateForm.target_audience"
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white">
                            <option value="all">All</option>
                            <option value="b2b">B2B</option>
                            <option value="b2c">B2C</option>
                            <option value="c2b">C2B</option>
                        </select>
                    </div>
                </div>

                {{-- Fee structure --}}
                <div class="grid grid-cols-3 gap-3 bg-slate-50 dark:bg-slate-800/60 p-3.5 rounded-xl border border-slate-200 dark:border-slate-700">
                    <div>
                        <label class="block text-[11px] font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Percent Rate (%) *</label>
                        <input type="number" step="0.01" min="0" wire:model="rateForm.percent_rate" placeholder="4.20"
                               class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-bold text-slate-800 dark:text-white" />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Fixed Fee (€) *</label>
                        <input type="number" step="0.01" min="0" wire:model="rateForm.fixed_fee" placeholder="0.30"
                               class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-bold text-slate-800 dark:text-white" />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Min Fee (€)</label>
                        <input type="number" step="0.01" min="0" wire:model="rateForm.min_fee" placeholder="0.60"
                               class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-bold text-slate-800 dark:text-white" />
                    </div>
                </div>

                {{-- Volume Tiers --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Min Volume Tier (€)</label>
                        <input type="number" step="10000" min="0" wire:model="rateForm.tier_min_volume" placeholder="0"
                               class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Max Volume Tier (€)</label>
                        <input type="number" step="10000" min="0" wire:model="rateForm.tier_max_volume" placeholder="2000000 (empty = no limit)"
                               class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Condition Notes</label>
                    <input type="text" wire:model="rateForm.notes" placeholder="e.g. Tier 1: €0 - €2M / mo"
                           class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white" />
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" wire:click="$set('showRateModal', false)"
                            class="px-4 py-2 text-sm font-semibold rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2 text-sm font-semibold rounded-xl bg-sky-600 hover:bg-sky-500 text-white shadow-md shadow-sky-600/20">
                        Save Rate
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- 3. Service Fee Modal --}}
    @if($showFeeModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-800 dark:text-white">
                    {{ $editingFeeId ? 'Edit Service Fee' : 'Add Service Fee' }}
                </h3>
                <button wire:click="$set('showFeeModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form wire:submit.prevent="saveFee" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Provider *</label>
                    <select wire:model="feeForm.provider_id"
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white">
                        @foreach($allProvidersList as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Fee Code *</label>
                        <select wire:model="feeForm.fee_code"
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white">
                            <option value="onboarding">Account Opening / Onboarding</option>
                            <option value="api_integration">API Integration & Support</option>
                            <option value="monthly_maintenance">Monthly Account Maintenance</option>
                            <option value="monthly_iban">Monthly Dedicated IBAN Fee</option>
                            <option value="chargeback">Chargeback Fee</option>
                            <option value="fx_margin">FX Margin / Commission</option>
                            <option value="crypto_exchange">Digital Crypto Exchange</option>
                            <option value="account_closing">Account Closing</option>
                            <option value="auditor_letter">Auditor Confirmation Letter</option>
                            <option value="custom">Other / Custom</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Fee Title *</label>
                        <input type="text" wire:model="feeForm.title" placeholder="Onboarding Fee"
                               class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Fee Model *</label>
                        <select wire:model="feeForm.fee_type"
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white">
                            <option value="fixed">Fixed (€)</option>
                            <option value="percentage">Percentage (%)</option>
                            <option value="range">Range (€ min – max)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Billing Period *</label>
                        <select wire:model="feeForm.billing_period"
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white">
                            <option value="one_time">One-time</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="per_event">Per Event / Transaction</option>
                        </select>
                    </div>
                </div>

                {{-- Amount / percentage inputs --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">
                            {{ $feeForm['fee_type'] === 'percentage' ? 'Percentage (%)' : 'Amount (€ / min)' }}
                        </label>
                        @if($feeForm['fee_type'] === 'percentage')
                            <input type="number" step="0.01" min="0" wire:model="feeForm.percentage" placeholder="2.00"
                                   class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white" />
                        @else
                            <input type="number" step="0.01" min="0" wire:model="feeForm.amount" placeholder="1000.00"
                                   class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white" />
                        @endif
                    </div>

                    @if($feeForm['fee_type'] === 'range')
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Amount Max (€)</label>
                        <input type="number" step="0.01" min="0" wire:model="feeForm.amount_max" placeholder="3000.00"
                               class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white" />
                    </div>
                    @endif
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Comments / Terms</label>
                    <input type="text" wire:model="feeForm.comment" placeholder="Terms and conditions for this fee..."
                           class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white" />
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" wire:click="$set('showFeeModal', false)"
                            class="px-4 py-2 text-sm font-semibold rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2 text-sm font-semibold rounded-xl bg-sky-600 hover:bg-sky-500 text-white shadow-md shadow-sky-600/20">
                        Save Fee
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- 4. Rolling Reserve Modal --}}
    @if($showReserveModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-800 dark:text-white">
                    Configure Rolling Reserve
                </h3>
                <button wire:click="$set('showReserveModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form wire:submit.prevent="saveReserve" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Hold Rate (%) *</label>
                        <input type="number" step="0.5" min="0" max="100" wire:model="reserveForm.rate_percent" placeholder="10.0"
                               class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold text-slate-800 dark:text-white" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Hold Period (Days) *</label>
                        <input type="number" step="1" min="0" wire:model="reserveForm.hold_period_days" placeholder="180"
                               class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold text-slate-800 dark:text-white" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Floor Amount (€)</label>
                        <input type="number" step="50" min="0" wire:model="reserveForm.floor_amount" placeholder="500"
                               class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-800 dark:text-white" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Cap Amount (€)</label>
                        <input type="number" step="1000" min="0" wire:model="reserveForm.cap_amount" placeholder="200000"
                               class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-800 dark:text-white" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Release Conditions</label>
                    <textarea wire:model="reserveForm.conditions" rows="2" placeholder="e.g. Held for 180 days on rolling cycle. Subject to merchant risk tier."
                              class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 dark:text-white"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" wire:click="$set('showReserveModal', false)"
                            class="px-4 py-2 text-sm font-semibold rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2 text-sm font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-600/20">
                        Save Policy
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
