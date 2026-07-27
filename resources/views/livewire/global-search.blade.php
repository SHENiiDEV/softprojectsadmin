<div x-data="{ open: false }" class="relative">
    <!-- Trigger Button -->
    <button @click="open = true; $nextTick(() => $refs.searchInput.focus())" 
            class="flex items-center space-x-2 px-4 py-2 bg-slate-50/50 hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-all text-xs font-semibold focus:outline-none">
        <i class="fa-solid fa-magnifying-glass text-indigo-500"></i>
        <span class="hidden sm:inline">Search...</span>
        <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 text-[9px] font-sans font-bold text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded shadow-xs ml-2 select-none">⌘K</kbd>
    </button>

    <!-- Global command palette modal -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         style="display: none;"
         @keydown.window.prevent.cmd.k="open = !open; if(open) { $nextTick(() => $refs.searchInput.focus()) }"
         @keydown.window.prevent.ctrl.k="open = !open; if(open) { $nextTick(() => $refs.searchInput.focus()) }"
         @click.self="open = false"
         @keydown.window.escape="open = false">
         
         <div class="relative w-full max-w-4xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[70vh]"
              @click.away="open = false">
              
              <!-- Search Input Header -->
              <div class="flex items-center px-6 py-4 bg-slate-50/50 dark:bg-slate-950/40 border-b border-slate-100 dark:border-slate-800/60 gap-3">
                  <!-- Regular search icon (hidden when loading) -->
                  <i class="fa-solid fa-magnifying-glass text-indigo-500 text-lg" wire:loading.remove wire:target="query"></i>
                  <!-- Spinner loader (visible when loading) -->
                  <i class="fa-solid fa-circle-notch fa-spin text-indigo-500 text-lg" wire:loading wire:target="query" style="display: none;"></i>
                  
                  <input type="text"
                         wire:model.live.debounce.250ms="query"
                         x-ref="searchInput"
                         placeholder="Type to search subscribers, bots, tickets, broadcasts, or shortcuts... (Esc to exit)"
                         class="flex-1 bg-transparent border-0 focus:ring-0 text-base font-bold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none"
                         @keydown.escape="open = false" />
                  
                  <kbd class="px-2 py-1 text-[10px] font-mono font-bold text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-sm select-none">ESC</kbd>
              </div>

              <!-- Search Results -->
              <div class="flex-1 overflow-y-auto p-6 max-h-[55vh] custom-scroll bg-white dark:bg-slate-900">
                  @php
                      $hasResults = false;
                      foreach($results as $group => $items) {
                          if(count($items) > 0) {
                              $hasResults = true;
                          }
                      }
                  @endphp

                  @if($hasResults)
                      <div class="space-y-6">
                          @foreach($results as $group => $items)
                              @if(count($items) > 0)
                                  <div>
                                      <!-- Group Header -->
                                      <div class="flex items-center space-x-2 mb-3">
                                          <span class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">
                                              {{ $groupNames[$group] ?? $group }}
                                          </span>
                                          <span class="h-px flex-1 bg-slate-100 dark:bg-slate-800/60"></span>
                                      </div>
                                      
                                      <!-- Group Grid -->
                                      <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                          @foreach($items as $item)
                                              <a href="{{ $item['url'] }}" 
                                                 class="group flex items-center p-3 rounded-2xl border border-transparent hover:border-indigo-100/60 dark:hover:border-indigo-950/40 hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 transition-all duration-200"
                                                 @if(str_starts_with($item['url'], 'http')) target="_blank" @endif
                                                 @click="open = false">
                                                  
                                                  <!-- Icon Wrapper -->
                                                  <div class="flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center transition-transform group-hover:scale-105 {{ $item['icon_bg'] ?? 'bg-slate-50 text-slate-600 dark:bg-slate-950 dark:text-slate-400' }}">
                                                      <i class="{{ $item['icon'] }} text-xs"></i>
                                                  </div>
                                                  
                                                  <!-- Text Wrapper -->
                                                  <div class="ml-3 min-w-0 flex-1">
                                                      <div class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors truncate">
                                                          {{ $item['title'] }}
                                                      </div>
                                                      @if(!empty($item['subtitle']))
                                                          <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate mt-0.5">
                                                              {{ $item['subtitle'] }}
                                                          </div>
                                                      @endif
                                                  </div>

                                                  <!-- Chevron Indicator -->
                                                  <div class="ml-2 opacity-0 group-hover:opacity-100 transition-opacity text-slate-400 dark:text-slate-600">
                                                      <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                                  </div>
                                              </a>
                                          @endforeach
                                      </div>
                                  </div>
                              @endif
                          @endforeach
                      </div>
                  @else
                      <!-- Empty State -->
                      <div class="p-12 text-center text-slate-400 dark:text-slate-500">
                          <div class="w-12 h-12 bg-slate-50 dark:bg-slate-950 rounded-full flex items-center justify-center mx-auto mb-3">
                              <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
                          </div>
                          <p class="text-sm">No results found for <span class="font-bold text-slate-600 dark:text-slate-300">"{{ $query }}"</span></p>
                      </div>
                  @endif
              </div>
         </div>
    </div>
</div>
