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
    <div class="pb-4 border-b border-slate-100 dark:border-slate-800/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h3 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Compliance KYB / KYC Control</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Management of legal procedures, bank verifications, and provider onboarding.</p>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap">
            <span class="text-xs font-semibold text-slate-400">Website Gateways:</span>
            @foreach($activeGateways as $gw)
                <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                    <i class="fa-solid fa-credit-card text-[10px] mr-1"></i> {{ $gw }}
                </span>
            @endforeach
        </div>
    </div>

    <!-- Active Providers Visual Status Badges Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($providers as $cKey => $pData)
            @php
                $pStatus = $pData['verification_status'] ?? $pData['boarding_status'] ?? 'pending';
                $gName = $pData['name'] ?? 'Provider';
                $siteName = $pData['website_name'] ?? '';
            @endphp
            <div class="p-4 bg-slate-50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-800/60 rounded-2xl flex flex-col justify-between space-y-2">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5 truncate" title="{{ $siteName }} - {{ $gName }}">
                        <i class="fa-solid fa-shield-halved text-sky-500"></i>
                        {{ $gName }} Status
                    </span>
                    @if($siteName)
                        <div class="text-xs font-semibold text-slate-600 dark:text-slate-300 truncate mt-0.5" title="{{ $siteName }}">
                            <i class="fa-solid fa-globe text-[10px] text-slate-400 mr-1"></i>{{ $siteName }}
                        </div>
                    @endif
                </div>
                <div class="flex items-center text-sm font-bold mt-1">
                    @if($pStatus === 'verified')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">Verified</span>
                    @elseif($pStatus === 'boarding_completed' || $pStatus === 'completed')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400">Boarding Completed</span>
                    @elseif($pStatus === 'in_progress')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400">In Progress</span>
                    @elseif($pStatus === 'need_to_upload')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400">Need to Upload</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400">Pending</span>
                    @endif
                </div>
            </div>
        @endforeach

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
    </div>

    <!-- Edit Form (Automatically synced per provider) -->
    <form wire:submit.prevent="save" class="space-y-6">
        
        {{-- Loop for Each Gateway per Website --}}
        @foreach($providers as $cKey => $pData)
            @php
                $gName = $pData['name'] ?? 'Provider';
                $siteName = $pData['website_name'] ?? '';
            @endphp
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                
                {{-- Provider Header --}}
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800/60 flex-wrap gap-2">
                    <div class="flex items-center space-x-3">
                        <span class="p-2.5 rounded-xl bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400 border border-sky-100 dark:border-sky-800/60">
                            <i class="fa-solid fa-building-columns text-base"></i>
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="font-outfit font-bold text-base text-slate-800 dark:text-white">
                                    Provider: {{ $gName }}
                                </h4>
                                @if($siteName)
                                    <span class="px-2.5 py-0.5 text-xs font-bold rounded-md bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                                        <i class="fa-solid fa-globe text-[10px] mr-1"></i> {{ $siteName }}
                                    </span>
                                @endif
                            </div>
                            <span class="text-xs text-slate-400">Compliance & boarding control for {{ $gName }} @if($siteName) on {{ $siteName }} @endif</span>
                        </div>
                    </div>

                    <span class="text-[11px] font-bold text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/40 px-3 py-1 rounded-lg border border-sky-200/60 dark:border-sky-800/60">
                        <i class="fa-solid fa-link text-[10px] mr-1"></i> Synced from Company Websites
                    </span>
                </div>

                {{-- Clean 4 Fields Grid per Provider --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <!-- {Provider} Boarding Completed Date -->
                    <div>
                        <label class="block text-xs font-bold text-sky-600 dark:text-sky-400 mb-1.5 truncate" title="{{ $gName }} Boarding Completed Date">
                            {{ $gName }} Boarding Completed Date
                        </label>
                        <input type="date" onclick="this.showPicker()" wire:model="providers.{{ $cKey }}.boarding_completed_at" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-sky-300 dark:border-sky-800/80 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                    </div>

                    <!-- {Provider} KYB Send -->
                    <div>
                        <label class="block text-xs font-bold text-sky-600 dark:text-sky-400 mb-1.5 truncate" title="{{ $gName }} KYB Send">
                            {{ $gName }} KYB Send
                        </label>
                        <select wire:model="providers.{{ $cKey }}.kyb_status" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-sky-300 dark:border-sky-800/80 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                            <option value="sent">Sent to {{ $gName }}</option>
                            <option value="in_progress">KYB In Review</option>
                            <option value="need_to_send">Need to Send</option>
                            <option value="verified">KYB Verified</option>
                        </select>
                    </div>

                    <!-- {Provider} Boarding Complete -->
                    <div>
                        <label class="block text-xs font-bold text-sky-600 dark:text-sky-400 mb-1.5 truncate" title="{{ $gName }} Boarding Complete">
                            {{ $gName }} Boarding Complete
                        </label>
                        <select wire:model="providers.{{ $cKey }}.boarding_status" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-sky-300 dark:border-sky-800/80 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                            <option value="boarding_completed">Boarding Completed</option>
                            <option value="verified">Verified</option>
                            <option value="in_progress">In Progress</option>
                            <option value="pending">Pending</option>
                            <option value="need_to_complete">Need to Complete</option>
                        </select>
                    </div>

                    <!-- {Provider} Verification Status -->
                    <div>
                        <label class="block text-xs font-bold text-sky-600 dark:text-sky-400 mb-1.5 truncate" title="{{ $gName }} Verification Status">
                            {{ $gName }} Verification Status
                        </label>
                        <select wire:model="providers.{{ $cKey }}.verification_status" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-sky-300 dark:border-sky-800/80 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                            <option value="verified">Verified</option>
                            <option value="boarding_completed">Boarding Completed</option>
                            <option value="completed">Completed</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="need_to_upload">Need to Upload</option>
                            <option value="rejected">Rejected / Declined</option>
                        </select>
                    </div>

                </div>
            </div>
        @endforeach

        {{-- Additional Legal & KYB Checks --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-4">
            <h4 class="font-semibold text-sm text-slate-800 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800/40 flex items-center">
                <i class="fa-solid fa-clipboard-check text-sky-500 mr-2"></i>
                General Legal Procedures & Registry Checks
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- KYB Completed At -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">KYB Completed Date</label>
                    <input type="date" onclick="this.showPicker()" wire:model="kyb_completed_at" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                    @error('kyb_completed_at') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Global Boarding Completed At -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Global Boarding Completed Date</label>
                    <input type="date" onclick="this.showPicker()" wire:model="boarding_completed_at" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                    @error('boarding_completed_at') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- CFS Verification Status -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">CFS Verification Status</label>
                    <select wire:model="cfs_verification" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                        <option value="verified">Verified</option>
                        <option value="boarding_completed">Boarding Completed</option>
                        <option value="need_to_complete">Need to Complete</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                    @error('cfs_verification') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold rounded-xl transition-all duration-150 shadow-md cursor-pointer hover:shadow-sky-500/25">
                Save Compliance & Provider Changes
            </button>
        </div>
    </form>
</div>
