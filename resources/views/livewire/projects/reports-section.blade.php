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
    <div class="pb-4 border-b border-slate-100 dark:border-slate-800/60">
        <h3 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Reports & Deadlines (Companies House)</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Calendar of mandatory financial and legal reports submission.</p>
    </div>

    <!-- Deadline Alert Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Accounts Due Card -->
        <div class="p-4 border rounded-2xl flex flex-col justify-between space-y-3 shadow-sm
            @if(is_null($daysUntilAccounts)) bg-slate-50 dark:bg-slate-950/20 border-slate-100 dark:border-slate-800/60
            @elseif($daysUntilAccounts < 0) bg-rose-50/60 border-rose-100 text-rose-800 dark:bg-rose-950/20 dark:border-rose-900/30 dark:text-rose-400
            @elseif($daysUntilAccounts <= 30) bg-amber-50/60 border-amber-100 text-amber-800 dark:bg-amber-950/20 dark:border-amber-900/30 dark:text-amber-400
            @else bg-emerald-50/60 border-emerald-100 text-emerald-800 dark:bg-emerald-950/20 dark:border-emerald-900/30 dark:text-emerald-400 @endif">
            
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Financial Statement (Accounts Due)</span>
                <svg class="h-5 w-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
            </div>

            <div>
                @if(is_null($daysUntilAccounts))
                    <span class="block text-sm font-semibold text-slate-500 dark:text-slate-400">Deadline not set</span>
                @else
                    <span class="block text-lg font-extrabold tracking-tight">
                        {{ $report->accounts_due_by->format('d.m.Y') }}
                    </span>
                    <span class="block text-xs font-medium mt-1">
                        @if($daysUntilAccounts < 0)
                            <span class="inline-flex items-center text-rose-600 dark:text-rose-400 font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600 dark:bg-rose-400 mr-1.5 animate-pulse"></span>
                                Overdue by {{ abs($daysUntilAccounts) }} days
                            </span>
                        @elseif($daysUntilAccounts <= 30)
                            <span class="inline-flex items-center text-amber-600 dark:text-amber-400 font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600 dark:bg-amber-400 mr-1.5 animate-pulse"></span>
                                Urgent: {{ $daysUntilAccounts }} days left
                            </span>
                        @else
                            <span class="text-emerald-600 dark:text-emerald-400">{{ $daysUntilAccounts }} days left</span>
                        @endif
                    </span>
                @endif
            </div>
        </div>

        <!-- Statements Due Card -->
        <div class="p-4 border rounded-2xl flex flex-col justify-between space-y-3 shadow-sm
            @if(is_null($daysUntilStatements)) bg-slate-50 dark:bg-slate-950/20 border-slate-100 dark:border-slate-800/60
            @elseif($daysUntilStatements < 0) bg-rose-50/60 border-rose-100 text-rose-800 dark:bg-rose-950/20 dark:border-rose-900/30 dark:text-rose-400
            @elseif($daysUntilStatements <= 30) bg-amber-50/60 border-amber-100 text-amber-800 dark:bg-amber-950/20 dark:border-amber-900/30 dark:text-amber-400
            @else bg-emerald-50/60 border-emerald-100 text-emerald-800 dark:bg-emerald-950/20 dark:border-emerald-900/30 dark:text-emerald-400 @endif">
            
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Confirmation Statement Due</span>
                <svg class="h-5 w-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <div>
                @if(is_null($daysUntilStatements))
                    <span class="block text-sm font-semibold text-slate-500 dark:text-slate-400">Deadline not set</span>
                @else
                    <span class="block text-lg font-extrabold tracking-tight">
                        {{ $report->statements_due_by->format('d.m.Y') }}
                    </span>
                    <span class="block text-xs font-medium mt-1">
                        @if($daysUntilStatements < 0)
                            <span class="inline-flex items-center text-rose-600 dark:text-rose-400 font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600 dark:bg-rose-400 mr-1.5 animate-pulse"></span>
                                Overdue by {{ abs($daysUntilStatements) }} days
                            </span>
                        @elseif($daysUntilStatements <= 30)
                            <span class="inline-flex items-center text-amber-600 dark:text-amber-400 font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600 dark:bg-amber-400 mr-1.5 animate-pulse"></span>
                                Urgent: {{ $daysUntilStatements }} days left
                            </span>
                        @else
                            <span class="text-emerald-600 dark:text-emerald-400">{{ $daysUntilStatements }} days left</span>
                        @endif
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <form wire:submit.prevent="save" class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-6">
        <h4 class="font-semibold text-sm text-slate-800 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800/40 flex items-center">
            <svg class="w-4 h-4 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 4a2 2 0 00-2-2h-3m3 3H9m3 3h-3m3 6h-3" />
            </svg>
            Companies House Details & Schedules
        </h4>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Reg Number -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Company Registration Number</label>
                <input type="text" wire:model="reg_number" placeholder="e.g. 12345678" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                @error('reg_number') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Auth Code -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Authorization Code (Auth Code)</label>
                <input type="text" wire:model="auth_code" placeholder="e.g. ABC123" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                @error('auth_code') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- CH Password -->
            <div x-data="{ showChPw: false }" class="relative">
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Password (CH Pass)</label>
                <input :type="showChPw ? 'text' : 'password'" wire:model="ch_pass" placeholder="••••••••••••" class="w-full pl-4 pr-10 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                
                <button type="button" @click="showChPw = !showChPw" class="absolute right-3.5 bottom-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                    <svg x-show="!showChPw" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showChPw" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
                @error('ch_pass') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Accounts Due Date -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Financial Report Deadline (Accounts Due)</label>
                <input type="date" onclick="this.showPicker()" wire:model="accounts_due_by" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                @error('accounts_due_by') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Confirmation Statement Due Date -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Confirmation Statement Deadline</label>
                <input type="date" onclick="this.showPicker()" wire:model="statements_due_by" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                @error('statements_due_by') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Registered Address -->
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Registered Address</label>
                <textarea wire:model="registered_address" rows="3" placeholder="Registered address..." class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150"></textarea>
                @error('registered_address') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex items-center justify-end pt-4 border-t border-slate-100 dark:border-slate-800/40">
            <button type="submit" class="px-5 py-2.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-400 border border-indigo-200/40 dark:border-indigo-800/40 rounded-xl shadow-sm hover:-translate-y-0.5 transition-all duration-200">
                Save Report Details
            </button>
        </div>
    </form>
</div>
