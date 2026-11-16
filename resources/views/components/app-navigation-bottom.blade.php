@props(['items'])

<nav aria-label="Mobile Navigation" class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border-t border-slate-200/80 dark:border-slate-800/80 shadow-[0_-4px_25px_rgba(0,0,0,0.06)] dark:shadow-[0_-4px_25px_rgba(0,0,0,0.4)]" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
    <div class="flex items-stretch justify-around h-16 max-w-lg mx-auto px-1">
        @foreach($items as $item)
            <a href="{{ $item['url'] }}"
               wire:navigate
               @if($item['is_active']) aria-current="page" @endif
               class="flex flex-1 flex-col items-center justify-center gap-0.5 transition-colors duration-150 {{ $item['is_active'] ? 'text-sky-600 dark:text-sky-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                <i class="fa-solid {{ $item['icon'] }} text-[17px]"></i>
                <span class="text-[10px] tracking-tight {{ $item['is_active'] ? 'font-semibold' : 'font-medium' }}">{{ $item['short'] ?? $item['label'] }}</span>
            </a>
        @endforeach

        <button type="button"
                @click="sidebarOpen = true"
                class="flex flex-1 flex-col items-center justify-center gap-0.5 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition-colors duration-150">
            <i class="fa-solid fa-bars text-[17px]"></i>
            <span class="text-[10px] tracking-tight font-medium">More</span>
        </button>
    </div>
</nav>
