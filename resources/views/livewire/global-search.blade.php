<div class="relative" x-data="{ open: false }">
    <input type="text"
           wire:model.live.debounce.300ms="query"
           placeholder="Search..."
           class="w-64 pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200"
           @focus="open = true"
           @keydown.escape="open = false" />
    <svg class="absolute inset-y-0 left-0 w-5 h-5 my-auto ml-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    <div x-show="open && $wire.results.length > 0" class="absolute z-10 w-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 rounded-xl shadow-lg">
        <ul>
            @foreach($results as $result)
                <li class="px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-800/30 transition-colors">
                    <a href="{{ $result['url'] }}" class="flex items-center space-x-2">
                        <i class="{{ $result['icon'] }} text-slate-500"></i>
                        <span class="text-sm text-slate-800 dark:text-slate-200">{{ $result['title'] }}</span>
                        <span class="ml-auto text-xs uppercase text-slate-400">{{ $result['type'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
