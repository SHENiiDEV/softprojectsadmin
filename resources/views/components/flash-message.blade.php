@props(['keys' => ['message' => 'success', 'error' => 'error']])

{{--
    Shared flash feedback for Livewire components: renders session('message') as success and session('error') as error.
    Auto-hides after 5 seconds and can be dismissed.
--}}
@foreach($keys as $key => $type)
    @if(session()->has($key))
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 5000)"
             x-transition.opacity.duration.300ms
             role="{{ $type === 'error' ? 'alert' : 'status' }}"
             {{ $attributes->class([
                 'flex items-center justify-between gap-3 px-4 py-3 rounded-xl border text-sm font-medium shadow-sm',
                 'bg-emerald-50 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-900/50 text-emerald-800 dark:text-emerald-300' => $type === 'success',
                 'bg-rose-50 dark:bg-rose-950/20 border-rose-200 dark:border-rose-900/50 text-rose-800 dark:text-rose-300' => $type === 'error',
             ]) }}>
            <div class="flex items-center gap-3">
                <i class="fa-solid {{ $type === 'error' ? 'fa-circle-exclamation text-rose-600 dark:text-rose-400' : 'fa-circle-check text-emerald-600 dark:text-emerald-400' }} text-lg"></i>
                <span>{{ session($key) }}</span>
            </div>
            <button type="button" @click="show = false" class="opacity-60 hover:opacity-100 transition-opacity" aria-label="Dismiss">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif
@endforeach
