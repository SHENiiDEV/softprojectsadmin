@php
    $providerTones = (include resource_path('views/onboarding/tones.php'))['provider'];
    $providerOptions = \App\Services\OnboardingBoard::PROVIDER_STATUSES;
    $emptyText ??= '—';
@endphp

<div class="flex flex-wrap gap-1.5">
    @forelse($providers as $provider)
        @if($canEdit && $projectId)
            <label class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 rounded-md border text-[11px] font-semibold {{ $providerTones[$provider['tone']] }}" title="Change the status of {{ $provider['name'] }}">
                <span>{{ $provider['name'] }}</span>
                <select wire:change="setProviderStatus({{ $projectId }}, '{{ addslashes($provider['name']) }}', $event.target.value)"
                        class="bg-transparent border-0 py-0 pl-1 pr-6 text-[11px] font-normal focus:ring-0 focus:outline-none cursor-pointer"
                        aria-label="{{ $provider['name'] }} status">
                    @foreach($providerOptions as $key => $label)
                        <option value="{{ $key }}" @selected($provider['key'] === $key) class="bg-white text-slate-800 dark:bg-slate-900 dark:text-slate-100">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        @else
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md border text-[11px] font-semibold {{ $providerTones[$provider['tone']] }}">{{ $provider['name'] }}<span class="font-normal opacity-80">· {{ $provider['status'] }}</span></span>
        @endif
    @empty
        <span class="text-sm text-slate-400">{{ $emptyText }}</span>
    @endforelse
</div>
