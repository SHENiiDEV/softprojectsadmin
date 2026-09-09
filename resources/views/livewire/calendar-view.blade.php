<div class="space-y-6">
    <x-slot name="header">
        Calendar Dashboard
    </x-slot>

    <!-- Header bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800/60">
        <div>
            <h1 class="font-outfit font-extrabold text-3xl text-slate-800 dark:text-white tracking-tight">Calendar Dashboard</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Deadlines, compliance filing dates, and scheduled project tasks.</p>
        </div>
        
        <!-- Legend Badges -->
        <div class="flex flex-wrap items-center gap-2.5 text-xs font-semibold">
            <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200/50 dark:border-sky-800/50 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-sky-500 mr-1.5"></span> Tasks (Medium)
            </span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200/50 dark:border-amber-800/50 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-amber-500 mr-1.5"></span> High Priority
            </span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200/50 dark:border-rose-800/50 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-rose-500 mr-1.5"></span> Critical
            </span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200/50 dark:border-purple-800/50 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-purple-500 mr-1.5"></span> Accounts Due
            </span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-pink-50 dark:bg-pink-950/40 text-pink-700 dark:text-pink-300 border border-pink-200/50 dark:border-pink-800/50 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-pink-500 mr-1.5"></span> Statements Due
            </span>
        </div>
    </div>

    <!-- Alert / Messages -->
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-400 flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400"></i>
                <span class="text-sm font-medium">{{ session('message') }}</span>
            </div>
            <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-800/40 text-rose-800 dark:text-rose-400 flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 dark:text-rose-400"></i>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
            <button @click="show = false" class="text-rose-500 hover:text-rose-700 dark:hover:text-rose-300">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- Interactive Filters Bar -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4">
            <!-- Project Filter -->
            <div class="lg:col-span-4">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Company / Project</label>
                <select wire:model.live="filterProject" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                    <option value="">All Companies (Projects)</option>
                    <option value="global">Global Tasks Only</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Assignee Filter -->
            <div class="lg:col-span-4">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Assignee / Team Member</label>
                <select wire:model.live="filterAssignee" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                    <option value="">All Team Members</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Task Status Filter -->
            <div class="lg:col-span-4">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Task Status</label>
                <select wire:model.live="filterStatus" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                    <option value="active">Active Tasks Only</option>
                    <option value="all">All Tasks (Active & Completed)</option>
                    <option value="done">Completed Tasks Only</option>
                </select>
            </div>
        </div>

        <!-- Event Type Toggles -->
        <div class="pt-3 border-t border-slate-100 dark:border-slate-800/60 flex flex-wrap items-center gap-5 text-xs">
            <span class="font-bold text-slate-400 dark:text-slate-500 text-[10px] uppercase tracking-wider">Show on Calendar:</span>
            
            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" wire:model.live="showTasks" class="w-4 h-4 rounded text-sky-600 focus:ring-sky-500/20 dark:bg-slate-950 dark:border-slate-800">
                <span class="font-semibold text-slate-700 dark:text-slate-300">📝 Tasks & Deadlines</span>
            </label>

            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" wire:model.live="showReports" class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500/20 dark:bg-slate-950 dark:border-slate-800">
                <span class="font-semibold text-slate-700 dark:text-slate-300">🏦 Compliance Filing Dates</span>
            </label>

            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" wire:model.live="showTimeLogs" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500/20 dark:bg-slate-950 dark:border-slate-800">
                <span class="font-semibold text-slate-700 dark:text-slate-300">⏱️ Tracked Work Time</span>
            </label>
        </div>
    </div>

    <!-- Calendar Card Container -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm">
        <div id="calendar" class="min-h-[650px] text-slate-700 dark:text-slate-200" wire:ignore></div>
    </div>

    <!-- FullCalendar v6 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script>
        let calendarInstance = null;

        document.addEventListener('livewire:initialized', () => {
            initCalendar();
            Livewire.hook('morph.updated', () => {
                refreshCalendarEvents();
            });
        });

        function initCalendar() {
            const calendarEl = document.getElementById('calendar');
            if (!calendarEl) return;

            const events = @json(json_decode($eventsJson));

            if (calendarInstance) {
                calendarInstance.destroy();
            }

            calendarInstance = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'en',
                editable: true,
                droppable: true,
                firstDay: 1, // Start week on Monday
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                },
                events: events,
                eventClick: function(info) {
                    if (info.event.url) {
                        info.jsEvent.preventDefault();
                        if (window.Livewire) {
                            Livewire.navigate(info.event.url);
                        } else {
                            window.location.href = info.event.url;
                        }
                    }
                },
                eventDrop: function(info) {
                    const eventId = info.event.id;
                    if (eventId && eventId.startsWith('task_')) {
                        const taskId = parseInt(eventId.replace('task_', ''));
                        const newDate = info.event.startStr; // YYYY-MM-DD
                        if (taskId && newDate) {
                            @this.call('updateTaskDueDate', taskId, newDate);
                        }
                    } else {
                        info.revert();
                    }
                },
                themeSystem: 'standard',
                height: 'auto'
            });

            calendarInstance.render();
        }

        function refreshCalendarEvents() {
            if (!calendarInstance) {
                initCalendar();
                return;
            }
            const events = @json(json_decode($eventsJson));
            calendarInstance.removeAllEvents();
            calendarInstance.addEventSource(events);
        }
    </script>

    <!-- Custom Styling for FullCalendar Dark Mode -->
    <style>
        .fc {
            --fc-border-color: rgba(226, 232, 240, 0.8);
            --fc-button-bg-color: #ffffff;
            --fc-button-border-color: #e2e8f0;
            --fc-button-text-color: #334155;
            --fc-button-hover-bg-color: #f8fafc;
            --fc-button-hover-border-color: #cbd5e1;
            --fc-button-active-bg-color: #f1f5f9;
            --fc-button-active-border-color: #cbd5e1;
            --fc-today-bg-color: rgba(14, 165, 233, 0.05);
        }
        .dark .fc {
            --fc-border-color: rgba(30, 41, 59, 0.8);
            --fc-button-bg-color: #0f172a;
            --fc-button-border-color: #1e293b;
            --fc-button-text-color: #94a3b8;
            --fc-button-hover-bg-color: #1e293b;
            --fc-button-hover-border-color: #334155;
            --fc-button-active-bg-color: #1e293b;
            --fc-button-active-border-color: #334155;
            --fc-today-bg-color: rgba(14, 165, 233, 0.1);
        }
        .fc .fc-toolbar-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.25rem;
        }
        .fc .fc-button {
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.5rem 0.875rem;
            text-transform: capitalize;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
        }
        .fc .fc-button-primary:not(:disabled).fc-button-active, 
        .fc .fc-button-primary:not(:disabled):active {
            background-color: var(--fc-button-active-bg-color) !important;
            border-color: var(--fc-button-active-border-color) !important;
            color: #0284c7 !important;
        }
        .dark .fc .fc-button-primary:not(:disabled).fc-button-active, 
        .dark .fc .fc-button-primary:not(:disabled):active {
            color: #38bdf8 !important;
        }
        .fc-event {
            border-radius: 0.5rem !important;
            padding: 0.25rem 0.5rem !important;
            font-weight: 550 !important;
            border: none !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            cursor: pointer;
            margin: 1px 2px !important;
        }
    </style>
</div>
