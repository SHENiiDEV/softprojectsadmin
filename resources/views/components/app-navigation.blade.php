@props(['groups', 'isDrawer' => false])

<nav class="flex-1 py-5 px-4 space-y-5 overflow-y-auto" aria-label="{{ $isDrawer ? 'Menu' : 'Main navigation' }}">
    @foreach($groups as $group)
        <div class="space-y-1">
            @if($group['label'])
                <p class="px-4 pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">{{ $group['label'] }}</p>
            @endif

            @foreach($group['items'] as $item)
                <a href="{{ $item['url'] }}"
                   wire:navigate
                   @if($isDrawer) @click="sidebarOpen = false" @endif
                   @if($item['is_active']) aria-current="page" @endif
                   class="group flex items-center px-4 py-2.5 text-sm font-medium rounded-xl transition-colors duration-150 {{ $item['is_active'] ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-400' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-800 dark:hover:text-white' }}">
                    <i class="fa-solid {{ $item['icon'] }} w-5 mr-3 text-center text-[15px] {{ $item['is_active'] ? '' : 'opacity-80 group-hover:opacity-100' }}"></i>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    @endforeach
</nav>
