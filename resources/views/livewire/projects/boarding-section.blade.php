@php
    $statusLabels = \App\Services\OnboardingBoard::FORM_STATUSES;
    $statusBadges = [
        'completed' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400',
        'in_progress' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400',
        'pending' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400',
        'rejected' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400',
    ];
    $field = 'w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-sky-300 dark:border-sky-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200';
    $label = 'block text-xs font-bold text-sky-600 dark:text-sky-400 mb-1.5 truncate';
@endphp

<div class="space-y-6">
    <x-flash-message />

    <!-- Header Section -->
    <div class="pb-4 border-b border-slate-100 dark:border-slate-800/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h3 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Compliance KYB / KYC Control</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Provider onboarding: status, send and completion dates, and how long it took.</p>
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

    <!-- Providers at a glance -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($providers as $cKey => $pData)
            @php
                $duration = $this->durationOf($pData);
            @endphp
            <div class="p-4 bg-slate-50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-800/60 rounded-2xl flex flex-col justify-between space-y-2">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5 truncate" title="{{ $pData['website_name'] ?? '' }} - {{ $pData['name'] }}">
                        <i class="fa-solid fa-shield-halved text-sky-500"></i>
                        {{ $pData['name'] }}
                    </span>
                    @if(! empty($pData['website_name']))
                        <div class="text-xs font-semibold text-slate-600 dark:text-slate-300 truncate mt-0.5" title="{{ $pData['website_name'] }}">
                            <i class="fa-solid fa-globe text-[10px] text-slate-400 mr-1"></i>{{ $pData['website_name'] }}
                        </div>
                    @endif
                </div>
                <div class="flex items-center justify-between gap-2 mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusBadges[$pData['status']] ?? $statusBadges['pending'] }}">{{ $statusLabels[$pData['status']] ?? 'Pending' }}</span>
                    @if($duration)
                        <span class="text-[11px] text-slate-500 dark:text-slate-400"><i class="fa-regular fa-clock mr-0.5"></i>{{ $duration }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Edit Form -->
    <form wire:submit.prevent="save" class="space-y-6">

        @foreach($providers as $cKey => $pData)
            @php
                $gName = $pData['name'] ?? 'Provider';
                $siteName = $pData['website_name'] ?? '';
                $duration = $this->durationOf($pData);
            @endphp
            <div wire:key="provider-{{ $cKey }}" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">

                {{-- Provider Header --}}
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800/60 flex-wrap gap-2">
                    <div class="flex items-center space-x-3">
                        <span class="p-2.5 rounded-xl bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400 border border-sky-100 dark:border-sky-800/60">
                            <i class="fa-solid fa-building-columns text-base"></i>
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="font-outfit font-bold text-base text-slate-800 dark:text-white">Provider: {{ $gName }}</h4>
                                @if($siteName)
                                    <span class="px-2.5 py-0.5 text-xs font-bold rounded-md bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                                        <i class="fa-solid fa-globe text-[10px] mr-1"></i> {{ $siteName }}
                                    </span>
                                @endif
                            </div>
                            <span class="text-xs text-slate-400">Boarding control for {{ $gName }}@if($siteName) on {{ $siteName }}@endif</span>
                        </div>
                    </div>

                    <span class="text-[11px] font-bold text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/40 px-3 py-1 rounded-lg border border-sky-200/60 dark:border-sky-800/60">
                        <i class="fa-solid fa-link text-[10px] mr-1"></i> Synced from Company Websites
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Boarding Status -->
                    <div>
                        <label class="{{ $label }}" title="{{ $gName }} Boarding Status">{{ $gName }} Boarding Status</label>
                        <select wire:model.live="providers.{{ $cKey }}.status" class="{{ $field }} cursor-pointer">
                            @foreach($statusLabels as $value => $text)
                                <option value="{{ $value }}">{{ $text }}</option>
                            @endforeach
                        </select>
                        @error("providers.{$cKey}.status") <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Boarding Send Date -->
                    <div>
                        <label class="{{ $label }}" title="{{ $gName }} Boarding Send Date">{{ $gName }} Boarding Send Date</label>
                        <input type="date" onclick="this.showPicker()" wire:model.live="providers.{{ $cKey }}.kyb_sent_at" class="{{ $field }}">
                        <p class="mt-1 text-[10px] text-slate-400">Set automatically when the status becomes In Progress.</p>
                        @error("providers.{$cKey}.kyb_sent_at") <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Boarding Complete Date -->
                    <div>
                        <label class="{{ $label }}" title="{{ $gName }} Boarding Complete Date">{{ $gName }} Boarding Complete Date</label>
                        <input type="date" onclick="this.showPicker()" wire:model.live="providers.{{ $cKey }}.boarding_completed_at" class="{{ $field }}">
                        <p class="mt-1 text-[10px] text-slate-400">Set automatically when the status becomes Completed.</p>
                        @error("providers.{$cKey}.boarding_completed_at") <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Duration -->
                    <div>
                        <label class="{{ $label }}">Boarding time</label>
                        <div class="px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-sm font-semibold {{ $duration ? 'text-slate-800 dark:text-slate-100' : 'text-slate-400' }}">
                            <i class="fa-regular fa-clock mr-1 text-slate-400"></i>{{ $duration ?? 'Not started yet' }}
                        </div>
                        <p class="mt-1 text-[10px] text-slate-400">From the send date to the completion date.</p>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold rounded-xl transition-all duration-150 shadow-md cursor-pointer">
                Save Provider Changes
            </button>
        </div>
    </form>
</div>
