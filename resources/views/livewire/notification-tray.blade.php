<div class="relative inline-block text-left" 
     x-data="{ 
         open: false,
         notifiedIds: JSON.parse(sessionStorage.getItem('notified_notification_ids') || '[]'),
         requestPermission() {
             if ('Notification' in window && Notification.permission === 'default') {
                 Notification.requestPermission();
             }
         },
         triggerPush(notifications) {
             if (!('Notification' in window) || Notification.permission !== 'granted') return;
             let changed = false;
             notifications.forEach(n => {
                 if (!this.notifiedIds.includes(n.id)) {
                     new Notification(n.data.title || 'New Notification', {
                         body: n.data.message || '',
                     });
                     this.notifiedIds.push(n.id);
                     changed = true;
                 }
             });
             if (changed) {
                 sessionStorage.setItem('notified_notification_ids', JSON.stringify(this.notifiedIds));
             }
         }
     }" 
     x-init="
         requestPermission();
         // Populate initial unread notifications to avoid spamming on page load
         const initial = JSON.parse($refs.notificationsData.textContent || '[]');
         initial.forEach(n => {
             if (!notifiedIds.includes(n.id)) {
                 notifiedIds.push(n.id);
             }
         });
         sessionStorage.setItem('notified_notification_ids', JSON.stringify(notifiedIds));

         // Watch for future updates
         $watch('$refs.notificationsData.textContent', (val) => {
             triggerPush(JSON.parse(val || '[]'));
         });
     "
     wire:poll.15s>
    <!-- Bell Button -->
    <button @click="open = !open" type="button" class="relative p-2.5 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all focus:outline-none" title="Notifications">
        <i class="fa-regular fa-bell text-lg"></i>
        @if($unreadCount > 0)
            <span class="absolute top-1.5 right-1.5 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-rose-500 text-[10px] font-bold text-white items-center justify-center select-none leading-none">
                    {{ $unreadCount }}
                </span>
            </span>
        @endif
    </button>

    <!-- Dropdown Panel -->
    <div x-show="open"
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl shadow-xl z-50 overflow-hidden"
         style="display: none;">
        
        <!-- Header -->
        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-950/40 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <span class="font-outfit font-bold text-sm text-slate-700 dark:text-slate-200">Notifications</span>
            @if($unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 font-semibold focus:outline-none transition-colors">
                    Mark all as read
                </button>
            @endif
        </div>

        <!-- Body -->
        <div class="max-h-96 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60">
            @forelse($notifications as $n)
                <div class="relative p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group flex items-start space-x-3">
                    
                    <!-- Icon container depending on type -->
                    <div class="flex-shrink-0 mt-0.5">
                        @switch($n->data['type'] ?? 'general')
                            @case('task_assigned')
                                <div class="h-8 w-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                    <i class="fa-solid fa-user-check text-xs"></i>
                                </div>
                                @break

                            @case('task_status_updated')
                                <div class="h-8 w-8 rounded-xl bg-sky-50 dark:bg-sky-950/30 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                                    <i class="fa-solid fa-arrows-rotate text-xs"></i>
                                </div>
                                @break

                            @case('client_portal_task_created')
                                <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                    <i class="fa-solid fa-globe text-xs"></i>
                                </div>
                                @break

                            @case('timer_action')
                                <div class="h-8 w-8 rounded-xl bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                    <i class="fa-solid fa-clock text-xs"></i>
                                </div>
                                @break

                            @default
                                <div class="h-8 w-8 rounded-xl bg-slate-50 dark:bg-slate-950/30 text-slate-600 dark:text-slate-400 flex items-center justify-center">
                                    <i class="fa-solid fa-bell text-xs"></i>
                                </div>
                        @endswitch
                    </div>

                    <!-- Details -->
                    <div class="flex-1 min-w-0 pr-6">
                        <a href="#" 
                           wire:click.prevent="markAsReadAndRedirect('{{ $n->id }}', '{{ $n->data['url'] ?? '#' }}')"
                           class="block text-xs font-semibold text-slate-800 dark:text-slate-200 hover:text-sky-600 dark:hover:text-sky-400 transition-colors mb-0.5 leading-tight">
                            {{ $n->data['title'] ?? 'Notification' }}
                        </a>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug">
                            {{ $n->data['message'] ?? '' }}
                        </p>
                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block mt-1">
                            {{ $n->created_at->diffForHumans() }}
                        </span>
                    </div>

                    <!-- Mark as read tick button -->
                    <button wire:click.stop="markAsRead('{{ $n->id }}')" 
                            title="Mark as read"
                            class="absolute right-4 top-4 opacity-0 group-hover:opacity-100 text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-400 transition-opacity focus:outline-none">
                        <i class="fa-solid fa-check text-xs"></i>
                    </button>
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <i class="fa-regular fa-bell text-2xl text-slate-300 dark:text-slate-700 mb-2 block mx-auto"></i>
                    <p class="text-xs text-slate-400 dark:text-slate-500">No new notifications</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Hidden element holding notification JSON -->
    <script x-ref="notificationsData" type="application/json">
        {!! json_encode($notifications->map(fn($n) => ['id' => $n->id, 'data' => $n->data])->toArray()) !!}
    </script>
</div>
