<div class="space-y-6">
    <x-slot name="header">
        Calendar Dashboard
    </x-slot>

    <!-- Header bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800/60">
        <div>
            <h1 class="font-outfit font-extrabold text-3xl text-slate-700 dark:text-white tracking-tight">Calendar Dashboard</h1>
            <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Deadlines, compliance filing dates, and scheduled project tasks.</p>
        </div>
        
        <!-- Legend -->
        <div class="flex flex-wrap items-center gap-3 text-xs font-semibold">
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-sky-50 dark:bg-sky-950/20 text-sky-600 dark:text-sky-400 border border-sky-100/50 dark:border-sky-900/30">
                <span class="w-1.5 h-1.5 rounded-full bg-sky-500 mr-1.5"></span> Tasks
            </span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-purple-50 dark:bg-purple-950/20 text-purple-600 dark:text-purple-400 border border-purple-100/55 dark:border-purple-900/30">
                <span class="w-1.5 h-1.5 rounded-full bg-purple-500 mr-1.5"></span> Accounts Due
            </span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-pink-50 dark:bg-pink-950/20 text-pink-600 dark:text-pink-400 border border-pink-100/55 dark:border-pink-900/30">
                <span class="w-1.5 h-1.5 rounded-full bg-pink-500 mr-1.5"></span> Statements Due
            </span>
        </div>
    </div>

    <!-- Calendar Wrapper Card -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm">
        <div id="calendar" class="min-h-[600px] text-slate-700 dark:text-slate-200"></div>
    </div>

    <!-- FullCalendar Script -->
    <script>
        document.addEventListener('livewire:navigated', () => {
            initializeCalendar();
        });

        document.addEventListener('DOMContentLoaded', () => {
            initializeCalendar();
        });

        function initializeCalendar() {
            const calendarEl = document.getElementById('calendar');
            if (!calendarEl) return;

            const events = @json(json_decode($eventsJson));

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'en',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
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
                themeSystem: 'standard',
                height: 'auto',
                firstDay: 1, // Start week on Monday
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    meridiem: false
                }
            });

            calendar.render();
        }
    </script>

    <!-- Custom Styling to theme FullCalendar for Premium Dark Mode -->
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
