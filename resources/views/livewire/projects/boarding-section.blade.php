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
    <div class="pb-4 border-b border-slate-100 dark:border-slate-800/60 flex items-center justify-between">
        <div>
            <h3 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Compliance KYB / KYC & Provider Control</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Management of legal procedures, bank verifications, and provider onboarding.</p>
        </div>

        <div class="inline-flex items-center gap-2 bg-slate-100 dark:bg-slate-800 p-1.5 rounded-xl border border-slate-200/60 dark:border-slate-700">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 pl-2">Active Provider:</span>
            <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-sky-600 text-white shadow-sm">
                {{ $provider_name ?: 'Cardaq' }}
            </span>
        </div>
    </div>

    <!-- Main Visual Status Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- CFS Status Card -->
        <div class="p-4 bg-slate-50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-800/60 rounded-2xl flex flex-col justify-between space-y-2">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">CFS Verification</span>
            <div class="flex items-center text-sm font-bold mt-1">
                @if($cfs_verification === 'verified')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">Verified</span>
                @elseif($cfs_verification === 'boarding_completed' || $cfs_verification === 'completed')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400">Boarding Completed</span>
                @elseif($cfs_verification === 'in_progress')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400">In Progress</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400">Need to Complete</span>
                @endif
            </div>
        </div>

        <!-- Provider Verification Card -->
        <div class="p-4 bg-slate-50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-800/60 rounded-2xl flex flex-col justify-between space-y-2">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $provider_name ?: 'Provider' }} Verification</span>
            <div class="flex items-center text-sm font-bold mt-1">
                @if($provider_verification_status === 'verified')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">Verified</span>
                @elseif($provider_verification_status === 'boarding_completed' || $provider_verification_status === 'completed')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400">Boarding Completed</span>
                @elseif($provider_verification_status === 'pending' || $provider_verification_status === 'in_progress')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400">Pending</span>
                @elseif($provider_verification_status === 'need_to_upload')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400">Need to Upload</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400">Rejected</span>
                @endif
            </div>
        </div>

        <!-- Bank Verification Card -->
        <div class="p-4 bg-slate-50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-800/60 rounded-2xl flex flex-col justify-between space-y-2">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Bank Verification</span>
            <div class="flex items-center text-sm font-bold mt-1">
                @if($bank_verification === 'verified')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">Verified</span>
                @elseif($bank_verification === 'boarding_completed' || $bank_verification === 'completed')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400">Boarding Completed</span>
                @elseif($bank_verification === 'pending')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400">Pending</span>
                @elseif($bank_verification === 'declined')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400">Declined</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400">Not Started</span>
                @endif
            </div>
        </div>

        <!-- Companies House Verification Card -->
        <div class="p-4 bg-slate-50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-800/60 rounded-2xl flex flex-col justify-between space-y-2">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Companies House</span>
            <div class="flex items-center text-sm font-bold mt-1">
                @if($companies_house_verification === 'verified')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">Verified</span>
                @elseif($companies_house_verification === 'boarding_completed' || $companies_house_verification === 'completed')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400">Boarding Completed</span>
                @elseif($companies_house_verification === 'pending')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400">Pending</span>
                @elseif($companies_house_verification === 'failed')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400">Failed</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400">Not Started</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <form wire:submit.prevent="save" class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-6">
        <h4 class="font-semibold text-sm text-slate-800 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800/40 flex items-center justify-between">
            <span class="flex items-center">
                <svg class="w-4 h-4 text-sky-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Update Compliance Statuses & Provider Dates
            </span>
        </h4>

        <!-- Provider Selection Row -->
        <div class="bg-slate-50 dark:bg-slate-950/40 p-4 rounded-xl border border-slate-100 dark:border-slate-800/60">
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Select Provider</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <select wire:model.live="provider_name" class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-sm font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                    <option value="Cardaq">Cardaq</option>
                    <option value="Madfin">Madfin</option>
                    <option value="SumSub">SumSub</option>
                    <option value="Stripe">Stripe</option>
                    <option value="Revolut">Revolut</option>
                    <option value="Wirecard">Wirecard</option>
                </select>
                <input type="text" wire:model.live="provider_name" placeholder="Or enter custom provider name..." class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- KYB Completed At -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">KYB Completed Date</label>
                <input type="date" onclick="this.showPicker()" wire:model="kyb_completed_at" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                @error('kyb_completed_at') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Global Boarding Completed At -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Boarding Completed Date</label>
                <input type="date" onclick="this.showPicker()" wire:model="boarding_completed_at" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                @error('boarding_completed_at') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- {Provider} Boarding Completed Date -->
            <div>
                <label class="block text-xs font-bold text-sky-600 dark:text-sky-400 mb-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-calendar-check text-xs"></i>
                    {{ $provider_name ?: 'Provider' }} Boarding Completed Date
                </label>
                <input type="date" onclick="this.showPicker()" wire:model="provider_boarding_completed_at" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-sky-300 dark:border-sky-800 rounded-xl text-sm font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                @error('provider_boarding_completed_at') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- {Provider} Verification Status -->
            <div>
                <label class="block text-xs font-bold text-sky-600 dark:text-sky-400 mb-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-shield-check text-xs"></i>
                    {{ $provider_name ?: 'Provider' }} Verification Status
                </label>
                <select wire:model="provider_verification_status" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-sky-300 dark:border-sky-800 rounded-xl text-sm font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                    <option value="verified">Verified (Верфай)</option>
                    <option value="boarding_completed">Boarding Completed (Боард комплит)</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="need_to_upload">Need to Upload</option>
                    <option value="rejected">Rejected / Declined</option>
                </select>
                @error('provider_verification_status') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- CFS Verification Status -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">CFS Verification Status</label>
                <select wire:model="cfs_verification" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                    <option value="verified">Verified (Верфай)</option>
                    <option value="boarding_completed">Boarding Completed (Боард комплит)</option>
                    <option value="need_to_complete">Need to Complete</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
                @error('cfs_verification') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Cardaq / Sumsub Status (Legacy Field) -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Cardaq / Sumsub Legacy Status</label>
                <select wire:model="cardaq_sumsub" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                    <option value="verified">Verified (Верфай)</option>
                    <option value="boarding_completed">Boarding Completed (Боард комплит)</option>
                    <option value="pending">Pending</option>
                    <option value="need_to_upload">Need to Upload</option>
                    <option value="completed">Completed</option>
                    <option value="rejected">Rejected</option>
                </select>
                @error('cardaq_sumsub') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Bank Verification -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Bank Verification</label>
                <select wire:model="bank_verification" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                    <option value="verified">Verified (Верфай)</option>
                    <option value="boarding_completed">Boarding Completed (Боард комплит)</option>
                    <option value="not_started">Not Started</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="declined">Declined</option>
                </select>
                @error('bank_verification') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Companies House Verification -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Companies House Registry</label>
                <select wire:model="companies_house_verification" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                    <option value="verified">Verified (Верфай)</option>
                    <option value="boarding_completed">Boarding Completed (Боард комплит)</option>
                    <option value="not_started">Not Started</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                </select>
                @error('companies_house_verification') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800/40">
            <button type="submit" class="px-5 py-2.5 bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold rounded-xl transition-all duration-150 shadow-sm cursor-pointer hover:shadow-sky-500/25">
                Save Compliance & Provider Changes
            </button>
        </div>
    </form>
</div>
