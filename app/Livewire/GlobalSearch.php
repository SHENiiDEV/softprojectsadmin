<?php

namespace App\Livewire;

use App\Models\SmmPost;
use App\Models\Task;
use App\Models\User;
use App\Models\Website;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GlobalSearch extends Component
{
    public $query = '';

    public $results = [
        'shortcuts' => [],
        'subscribers' => [],
        'bots' => [],
        'tickets' => [],
        'broadcasts' => [],
        'campaigns' => [],
    ];

    public array $groupNames = [
        'shortcuts' => 'Navigation Shortcuts',
        'subscribers' => 'Subscribers',
        'bots' => 'Telegram Bots',
        'tickets' => 'Support Tickets',
        'broadcasts' => 'Broadcasts',
        'campaigns' => 'Tracking Campaigns',
    ];

    public function mount()
    {
        $this->updatedQuery();
    }

    public function updatedQuery()
    {
        $this->results = [
            'shortcuts' => [],
            'subscribers' => [],
            'bots' => [],
            'tickets' => [],
            'broadcasts' => [],
            'campaigns' => [],
        ];

        // 1. Navigation Shortcuts
        $rawShortcuts = [
            [
                'title' => 'Dashboard',
                'url' => route('dashboard'),
                'icon' => 'fa-solid fa-chart-line',
                'subtitle' => 'Overview statistics and widgets',
                'icon_bg' => 'bg-white border dark:bg-slate-950 dark:border-slate-800/80 shadow-2xs text-slate-600 dark:text-slate-400',
                'enabled' => true,
            ],
            [
                'title' => 'Clients',
                'url' => route('clients.index'),
                'icon' => 'fa-solid fa-users',
                'subtitle' => 'Manage client accounts and details',
                'icon_bg' => 'bg-white border dark:bg-slate-950 dark:border-slate-800/80 shadow-2xs text-slate-600 dark:text-slate-400',
                'enabled' => config('features.clients'),
            ],
            [
                'title' => 'Companies',
                'url' => route('projects.index'),
                'icon' => 'fa-solid fa-building',
                'subtitle' => 'Manage companies, websites, and settings',
                'icon_bg' => 'bg-white border dark:bg-slate-950 dark:border-slate-800/80 shadow-2xs text-slate-600 dark:text-slate-400',
                'enabled' => auth()->user()?->can('view_projects') ?? false,
            ],
            [
                'title' => 'Tasks (Kanban)',
                'url' => route('tasks.kanban'),
                'icon' => 'fa-solid fa-list-check',
                'subtitle' => 'Kanban board and task management',
                'icon_bg' => 'bg-white border dark:bg-slate-950 dark:border-slate-800/80 shadow-2xs text-slate-600 dark:text-slate-400',
                'enabled' => config('features.kanban') && (auth()->user()?->can('view_tasks') ?? false),
            ],
            [
                'title' => 'Calendar',
                'url' => route('calendar'),
                'icon' => 'fa-solid fa-calendar-days',
                'subtitle' => 'Monthly calendar view of deadlines',
                'icon_bg' => 'bg-white border dark:bg-slate-950 dark:border-slate-800/80 shadow-2xs text-slate-600 dark:text-slate-400',
                'enabled' => config('features.calendar') && (auth()->user()?->can('view_calendar') ?? false),
            ],
            [
                'title' => 'My Work',
                'url' => route('my.work'),
                'icon' => 'fa-solid fa-briefcase',
                'subtitle' => 'Personal workload and active timers',
                'icon_bg' => 'bg-white border dark:bg-slate-950 dark:border-slate-800/80 shadow-2xs text-slate-600 dark:text-slate-400',
                'enabled' => config('features.my_work') && (auth()->user()?->can('view_tasks') ?? false),
            ],
            [
                'title' => 'Deadlines Center',
                'url' => route('deadlines'),
                'icon' => 'fa-solid fa-clock',
                'subtitle' => 'Upcoming statement and accounts dates',
                'icon_bg' => 'bg-white border dark:bg-slate-950 dark:border-slate-800/80 shadow-2xs text-slate-600 dark:text-slate-400',
                'enabled' => config('features.deadline_center') && (auth()->user()?->can('view_deadlines') ?? false),
            ],
            [
                'title' => 'Health Score',
                'url' => route('health.score'),
                'icon' => 'fa-solid fa-heart-pulse',
                'subtitle' => 'Automated compliance checklist scores',
                'icon_bg' => 'bg-white border dark:bg-slate-950 dark:border-slate-800/80 shadow-2xs text-slate-600 dark:text-slate-400',
                'enabled' => config('features.health_score') && (auth()->user()?->can('view_projects') ?? false),
            ],
            [
                'title' => 'Activity Center',
                'url' => route('activity.center'),
                'icon' => 'fa-solid fa-bolt',
                'subtitle' => 'Global logs of system actions',
                'icon_bg' => 'bg-white border dark:bg-slate-950 dark:border-slate-800/80 shadow-2xs text-slate-600 dark:text-slate-400',
                'enabled' => config('features.activity_center') && (auth()->user()?->can('view_activity') ?? false),
            ],
            [
                'title' => 'Credentials Vault',
                'url' => route('credentials'),
                'icon' => 'fa-solid fa-key',
                'subtitle' => 'Secure passwords and credentials',
                'icon_bg' => 'bg-white border dark:bg-slate-950 dark:border-slate-800/80 shadow-2xs text-slate-600 dark:text-slate-400',
                'enabled' => config('features.credential_vault'),
            ],
            [
                'title' => 'Time Log Report',
                'url' => route('reports.time'),
                'icon' => 'fa-solid fa-hourglass-half',
                'subtitle' => 'Time tracking analytics and breakdown',
                'icon_bg' => 'bg-white border dark:bg-slate-950 dark:border-slate-800/80 shadow-2xs text-slate-600 dark:text-slate-400',
                'enabled' => config('features.time_tracking') && (auth()->user()?->can('view_reports') ?? false),
            ],
            [
                'title' => 'Productivity Report',
                'url' => route('reports.productivity'),
                'icon' => 'fa-solid fa-gauge-high',
                'subtitle' => 'Team leaderboard and productivity stats',
                'icon_bg' => 'bg-white border dark:bg-slate-950 dark:border-slate-800/80 shadow-2xs text-slate-600 dark:text-slate-400',
                'enabled' => config('features.time_tracking') && (auth()->user()?->can('view_reports') ?? false),
            ],
            [
                'title' => 'System Settings',
                'url' => route('settings'),
                'icon' => 'fa-solid fa-sliders',
                'subtitle' => 'Hub options and parameters configuration',
                'icon_bg' => 'bg-white border dark:bg-slate-950 dark:border-slate-800/80 shadow-2xs text-slate-600 dark:text-slate-400',
                'enabled' => config('features.settings_panel'),
            ],
            [
                'title' => 'User Profile',
                'url' => route('profile'),
                'icon' => 'fa-solid fa-user-gear',
                'subtitle' => 'Update profile details and Telegram link',
                'icon_bg' => 'bg-white border dark:bg-slate-950 dark:border-slate-800/80 shadow-2xs text-slate-600 dark:text-slate-400',
                'enabled' => true,
            ],
        ];

        foreach ($rawShortcuts as $s) {
            if ($s['enabled']) {
                if (empty($this->query) || stripos($s['title'], $this->query) !== false || stripos($s['subtitle'], $this->query) !== false) {
                    $this->results['shortcuts'][] = $s;
                }
            }
        }

        if (strlen($this->query) < 2) {
            return;
        }

        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        // 2. Subscribers (Users search)
        $users = User::where('name', $like, "%{$this->query}%")
            ->orWhere('email', $like, "%{$this->query}%")
            ->orWhere('telegram_id', $like, "%{$this->query}%")
            ->limit(6)
            ->get();
        foreach ($users as $u) {
            $telegramDesc = $u->telegram_id ? "Telegram ID: {$u->telegram_id}" : 'No linked Telegram';
            $this->results['subscribers'][] = [
                'title' => $u->name,
                'subtitle' => "{$u->email} | {$telegramDesc}",
                'url' => route('users.index').'?search='.urlencode($u->email),
                'icon' => 'fas fa-user',
                'icon_bg' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400',
            ];
        }

        // 3. Telegram Bots
        $botUsername = config('services.telegram.bot_username', 'softprojectshubbot');
        if (empty($botUsername)) {
            $botUsername = 'softprojectshubbot';
        }
        if (stripos($botUsername, $this->query) !== false || stripos('telegram bot', $this->query) !== false) {
            $this->results['bots'][] = [
                'title' => 'SoftProjects Hub Bot',
                'subtitle' => "@{$botUsername}",
                'url' => 'https://t.me/'.$botUsername,
                'icon' => 'fas fa-robot',
                'icon_bg' => 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400',
            ];
        }
        $botWebsites = Website::where('name', $like, "%{$this->query}%")
            ->where(function ($q) use ($like) {
                $q->where('name', $like, '%bot%')
                    ->orWhere('url', $like, '%bot%');
            })
            ->limit(3)
            ->get();
        foreach ($botWebsites as $bw) {
            $this->results['bots'][] = [
                'title' => $bw->name,
                'subtitle' => $bw->url,
                'url' => route('projects.show', $bw->project_id),
                'icon' => 'fas fa-robot',
                'icon_bg' => 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400',
            ];
        }

        // 4. Support Tickets (Tasks search)
        $tasks = Task::where('title', $like, "%{$this->query}%")
            ->orWhere('description', $like, "%{$this->query}%")
            ->limit(6)
            ->with('project')
            ->get();
        foreach ($tasks as $t) {
            $projectStr = $t->project ? $t->project->name : 'Global Task';
            $statusStr = str_replace('_', ' ', strtoupper($t->status));
            $priorityStr = strtoupper($t->priority);
            $this->results['tickets'][] = [
                'title' => $t->title,
                'subtitle' => "[{$statusStr}] Priority: {$priorityStr} | {$projectStr}",
                'url' => route('tasks.kanban', ['task_id' => $t->id]),
                'icon' => 'fas fa-ticket-alt',
                'icon_bg' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400',
            ];
        }

        // 5. Broadcasts (SmmPost search)
        $posts = SmmPost::where('title', $like, "%{$this->query}%")
            ->orWhere('content', $like, "%{$this->query}%")
            ->limit(6)
            ->get();
        foreach ($posts as $p) {
            $dateStr = $p->published_at ? $p->published_at->format('d M Y H:i') : 'Draft';
            $this->results['broadcasts'][] = [
                'title' => $p->title,
                'subtitle' => 'Platform: '.ucfirst($p->platform).' | Status: '.ucfirst($p->status).' | '.$dateStr,
                'url' => route('projects.show', $p->project_id),
                'icon' => 'fas fa-paper-plane',
                'icon_bg' => 'bg-pink-50 text-pink-600 dark:bg-pink-950/40 dark:text-pink-400',
            ];
        }

        // 6. Tracking Campaigns (Website search)
        $websites = Website::where('name', $like, "%{$this->query}%")
            ->orWhere('url', $like, "%{$this->query}%")
            ->limit(6)
            ->get();
        foreach ($websites as $w) {
            $visa = $w->visa_status ?? 'Stopped';
            $mc = $w->mastercard_status ?? 'Stopped';
            $this->results['campaigns'][] = [
                'title' => $w->name,
                'subtitle' => "URL: {$w->url} | VISA: {$visa} | MC: {$mc}",
                'url' => route('projects.show', $w->project_id),
                'icon' => 'fas fa-link',
                'icon_bg' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400',
            ];
        }
    }

    public function render()
    {
        return view('livewire.global-search');
    }
}
