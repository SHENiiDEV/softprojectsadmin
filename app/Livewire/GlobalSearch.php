<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Comment;
use App\Models\Credential;
use App\Models\PciDssDocument;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\Provider;
use App\Models\SmmPost;
use App\Models\SupportTicket;
use App\Models\Task;
use App\Models\User;
use App\Models\Website;
use App\Services\NavigationMenu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Command palette: one query is looked up everywhere the user is allowed to see.
 * Several words must all match (each word in any field of the same record).
 */
class GlobalSearch extends Component
{
    private const LIMIT = 6;

    private const ICON_TONES = [
        'slate' => 'bg-white border dark:bg-slate-950 dark:border-slate-800/80 shadow-2xs text-slate-600 dark:text-slate-400',
        'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-950/40 dark:text-sky-400',
        'indigo' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400',
        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400',
        'rose' => 'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400',
        'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-950/40 dark:text-violet-400',
        'pink' => 'bg-pink-50 text-pink-600 dark:bg-pink-950/40 dark:text-pink-400',
    ];

    public $query = '';

    /** @var array<string, list<array<string, string>>> */
    public $results = [];

    /** @var array<string, string> */
    public array $groupNames = [
        'shortcuts' => 'Navigation',
        'companies' => 'Companies',
        'tasks' => 'Tasks',
        'comments' => 'Comments & notes',
        'websites' => 'Websites',
        'clients' => 'Clients',
        'tickets' => 'Email tickets',
        'documents' => 'PCI DSS documents',
        'credentials' => 'Credentials',
        'providers' => 'Providers',
        'users' => 'Team',
        'posts' => 'SMM posts',
    ];

    private string $like = 'like';

    /** @var list<string> */
    private array $terms = [];

    public function mount()
    {
        $this->updatedQuery();
    }

    public function updatedQuery()
    {
        $this->results = array_fill_keys(array_keys($this->groupNames), []);
        $this->like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query = trim((string) $this->query);
        $this->terms = array_slice(preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 5);

        $this->results['shortcuts'] = $this->shortcuts($query);

        if (mb_strlen($query) < 2) {
            return;
        }

        $user = auth()->user();
        $can = fn (string $permission) => $user?->can($permission) ?? false;

        if ($can('view_projects')) {
            $this->results['companies'] = $this->companies();
            if (config('features.websites_tab', true)) {
                $this->results['websites'] = $this->websites();
            }
            if (config('features.smm') && config('features.operations_tab', true)) {
                $this->results['posts'] = $this->posts();
            }
        }

        if ($can('view_tasks')) {
            $this->results['tasks'] = $this->tasks();
            $this->results['tickets'] = $this->tickets();
        }

        $this->results['comments'] = $this->commentsAndNotes($can('view_tasks'), $can('view_projects'));

        if ($can('view_clients') && config('features.clients')) {
            $this->results['clients'] = $this->clients();
        }

        if ($can('view_pci_dss') && config('features.pci_dss')) {
            $this->results['documents'] = $this->documents();
        }

        if ($can('view_credentials') && config('features.credential_vault')) {
            $this->results['credentials'] = $this->credentials();
        }

        if ($can('view_tariffs') && config('features.tariffs')) {
            $this->results['providers'] = $this->providers();
        }

        if ($can('manage_users')) {
            $this->results['users'] = $this->users();
        }
    }

    /**
     * Pages the user can open (same list as the menu), filtered by the query.
     *
     * @return list<array<string, string>>
     */
    private function shortcuts(string $query): array
    {
        $user = auth()->user();
        if ($user === null) {
            return [];
        }

        $items = [];
        foreach (app(NavigationMenu::class)->groups($user) as $group) {
            foreach ($group['items'] as $item) {
                $items[] = [
                    'title' => $item['label'],
                    'subtitle' => $group['label'] ?? 'Navigation',
                    'url' => $item['url'],
                    'icon' => 'fa-solid '.$item['icon'],
                    'icon_bg' => self::ICON_TONES['slate'],
                ];
            }
        }

        $items[] = [
            'title' => 'User Profile',
            'subtitle' => 'Update profile details and Telegram link',
            'url' => route('profile'),
            'icon' => 'fa-solid fa-user-gear',
            'icon_bg' => self::ICON_TONES['slate'],
        ];

        if ($query === '') {
            return $items;
        }

        return array_values(array_filter($items, fn (array $item) => stripos($item['title'].' '.$item['subtitle'], $query) !== false));
    }

    /**
     * Companies, found by their own data and by their director, client, websites and providers.
     *
     * @return list<array<string, string>>
     */
    private function companies(): array
    {
        $columns = ['name', 'ubo', 'mcc', 'notes', 'settlement_bank_1', 'settlement_bank_2', 'director.name', 'client.name', 'websites.name', 'websites.url'];
        if ($this->like === 'like') {
            // JSON columns are plain text for LIKE on MySQL / SQLite
            array_push($columns, 'phones', 'emails', 'websites.gateways', 'boarding.providers_data');
        }

        $query = Project::query()->with(['director', 'client', 'websites', 'boarding'])->orderBy('name');
        $this->matches($query, $columns, $this->terms);

        return $this->rank($query->limit(self::LIMIT)->get()->map(function (Project $project) {
            $reason = $this->companyReason($project);

            return [
                'title' => $project->name,
                'subtitle' => implode(' · ', array_filter([
                    ucfirst((string) $project->status),
                    $project->archived_at ? 'Archived' : null,
                    $reason,
                ])),
                'url' => route('projects.show', $project),
                'icon' => 'fa-solid fa-building',
                'icon_bg' => self::ICON_TONES['sky'],
            ];
        })->all());
    }

    /**
     * Which field of the company matched, so a hit on the director does not look like a mystery.
     */
    private function companyReason(Project $project): ?string
    {
        $fields = [
            'Director Curator' => $project->ubo,
            'Director' => $project->director?->name,
            'Client' => $project->client?->name,
            'Website' => $project->websites->map(fn (Website $w) => $w->name.' '.$w->url.' '.implode(' ', (array) $w->gateways))->implode(' | '),
            'Provider' => json_encode($project->boarding?->providers_data, JSON_UNESCAPED_UNICODE),
            'MCC' => $project->mcc,
            'Bank' => $project->settlement_bank_1.' '.$project->settlement_bank_2,
            'Phone' => json_encode($project->phones, JSON_UNESCAPED_UNICODE),
            'Email' => json_encode($project->emails, JSON_UNESCAPED_UNICODE),
            'Notes' => $project->notes,
        ];

        // A hit on the company name itself needs no explanation
        if ($this->anyTermIn($project->name)) {
            return $project->client?->name ? 'Client: '.$project->client->name : null;
        }

        foreach ($fields as $label => $value) {
            if ($value !== null && $this->anyTermIn((string) $value)) {
                return $label.': '.($label === 'Notes' || $label === 'Website' || $label === 'Provider' || $label === 'Phone' || $label === 'Email' || $label === 'Bank'
                    ? $this->excerpt((string) $value, 50)
                    : Str::limit((string) $value, 60));
            }
        }

        return null;
    }

    /**
     * @return list<array<string, string>>
     */
    private function tasks(): array
    {
        $query = Task::query()->with(['project:id,name', 'assignee:id,name'])->orderByDesc('id');
        $this->matches($query, ['title', 'description', 'current_status', 'onboarding_status', 'project.name', 'assignee.name'], $this->terms, function (Builder $q, string $term) {
            // "kyb", "pci" or "client request" also finds tasks by their type
            $types = collect(Task::TYPES)->filter(fn (string $label, string $key) => stripos($label, $term) !== false || stripos($key, $term) !== false)->keys()->all();
            if ($types !== []) {
                $q->orWhereIn('type', $types);
            }
        });

        return $this->rank($query->limit(self::LIMIT)->get()->map(fn (Task $task) => [
            'title' => $task->title,
            'subtitle' => implode(' · ', array_filter([
                Task::TYPES[$task->type] ?? null,
                str_replace('_', ' ', ucfirst((string) $task->status)),
                $task->project?->name ?? 'Global task',
                $task->assignee?->name,
                $task->isOnboardingType() ? $task->onboarding_status : null,
            ])),
            'url' => route('tasks.kanban', ['task_id' => $task->id]),
            'icon' => 'fa-solid fa-list-check',
            'icon_bg' => self::ICON_TONES['amber'],
        ])->all());
    }

    /**
     * @return list<array<string, string>>
     */
    private function tickets(): array
    {
        $query = SupportTicket::query()->with('project:id,name')->orderByDesc('id');
        $this->matches($query, ['subject', 'customer_email', 'recipient_email', 'project.name'], $this->terms);

        return $this->rank($query->limit(self::LIMIT)->get()->map(fn (SupportTicket $ticket) => [
            'title' => $ticket->subject ?: '(no subject)',
            'subtitle' => implode(' · ', array_filter([ucfirst((string) $ticket->status), $ticket->customer_email, $ticket->project?->name])),
            'url' => $ticket->task_id ? route('tasks.kanban', ['task_id' => $ticket->task_id]) : ($ticket->project_id ? route('projects.show', $ticket->project_id) : route('tasks.kanban')),
            'icon' => 'fa-solid fa-envelope',
            'icon_bg' => self::ICON_TONES['pink'],
        ])->all());
    }

    /**
     * Task comments, company comments and company notes by keyword, with a snippet around the match.
     *
     * @return list<array<string, string>>
     */
    private function commentsAndNotes(bool $canTasks, bool $canProjects): array
    {
        $items = [];

        if ($canTasks || ($canProjects && config('features.company_comments', true))) {
            $query = Comment::query()->with(['task:id,title', 'project:id,name', 'user:id,name'])->orderByDesc('id');
            $this->matches($query, ['content'], $this->terms);
            $query->where(function (Builder $q) use ($canTasks, $canProjects) {
                if ($canTasks) {
                    $q->orWhereNotNull('task_id');
                }
                if ($canProjects && config('features.company_comments', true)) {
                    $q->orWhere(fn (Builder $c) => $c->whereNull('task_id')->whereNotNull('project_id'));
                }
            });

            foreach ($query->limit(self::LIMIT)->get() as $comment) {
                $onTask = $comment->task_id !== null;

                $items[] = [
                    'title' => $onTask ? ($comment->task?->title ?? 'Task') : ($comment->project?->name ?? 'Company'),
                    'subtitle' => ($comment->user?->name ?? 'Client').': '.$this->excerpt((string) $comment->content),
                    'url' => $onTask ? route('tasks.kanban', ['task_id' => $comment->task_id]) : route('projects.show', $comment->project_id).'?tab=comments',
                    'icon' => 'fa-regular fa-comment',
                    'icon_bg' => self::ICON_TONES['violet'],
                ];
            }
        }

        if ($canProjects && config('features.notes_tab', true)) {
            $query = ProjectNote::query()->with(['project:id,name', 'user:id,name'])->orderByDesc('id');
            $this->matches($query, ['content'], $this->terms);

            foreach ($query->limit(self::LIMIT)->get() as $note) {
                $items[] = [
                    'title' => ($note->project?->name ?? 'Company').' · Note',
                    'subtitle' => ($note->user?->name ?? 'Team').': '.$this->excerpt((string) $note->content),
                    'url' => route('projects.show', $note->project_id).'?tab=notes',
                    'icon' => 'fa-regular fa-note-sticky',
                    'icon_bg' => self::ICON_TONES['violet'],
                ];
            }
        }

        return $items;
    }

    /**
     * @return list<array<string, string>>
     */
    private function websites(): array
    {
        $columns = ['name', 'url', 'project.name', 'responsible.name'];
        if ($this->like === 'like') {
            $columns[] = 'gateways';
        }

        $query = Website::query()->with(['project:id,name', 'responsible:id,name'])->orderByDesc('id');
        $this->matches($query, $columns, $this->terms);

        return $this->rank($query->limit(self::LIMIT)->get()->map(fn (Website $website) => [
            'title' => $website->name ?: (string) $website->url,
            'subtitle' => implode(' · ', array_filter([
                $website->url,
                $website->project?->name,
                $website->responsible ? 'Responsible: '.$website->responsible->name : null,
                $website->status,
            ])),
            'url' => route('projects.show', $website->project_id).'?tab=websites',
            'icon' => 'fa-solid fa-link',
            'icon_bg' => self::ICON_TONES['indigo'],
        ])->all());
    }

    /**
     * @return list<array<string, string>>
     */
    private function clients(): array
    {
        $query = Client::query()->withCount('companies')->orderBy('name');
        $this->matches($query, ['name'], $this->terms);

        return $this->rank($query->limit(self::LIMIT)->get()->map(fn (Client $client) => [
            'title' => $client->name,
            'subtitle' => $client->companies_count.' '.Str::plural('company', $client->companies_count),
            'url' => route('clients.index', ['search' => $client->name]),
            'icon' => 'fa-solid fa-users',
            'icon_bg' => self::ICON_TONES['emerald'],
        ])->all());
    }

    /**
     * @return list<array<string, string>>
     */
    private function documents(): array
    {
        $query = PciDssDocument::query()->with(['client:id,name', 'project:id,name'])->orderByDesc('id');
        $this->matches($query, ['title', 'custom_type', 'document_type', 'file_name', 'notes', 'client.name', 'project.name'], $this->terms);

        return $this->rank($query->limit(self::LIMIT)->get()->map(fn (PciDssDocument $document) => [
            'title' => $document->title,
            'subtitle' => implode(' · ', array_filter([
                $document->custom_type ?: $document->document_type,
                $document->client?->name ?? $document->project?->name,
                $document->valid_until ? 'Valid until '.$document->valid_until->format('d.m.Y') : null,
            ])),
            'url' => route('pci-dss.index', ['search' => $document->title]),
            'icon' => 'fa-solid fa-shield-halved',
            'icon_bg' => self::ICON_TONES['rose'],
        ])->all());
    }

    /**
     * Credentials by name, login and URL (never by password); passwords are not shown either.
     *
     * @return list<array<string, string>>
     */
    private function credentials(): array
    {
        $query = Credential::query()->with(['project:id,name', 'website:id,name'])->orderByDesc('id');
        $this->matches($query, ['name', 'login', 'provider_url', 'project.name', 'website.name'], $this->terms);

        return $this->rank($query->limit(self::LIMIT)->get()->map(fn (Credential $credential) => [
            'title' => $credential->name ?: ucfirst(str_replace('_', ' ', (string) $credential->type)),
            'subtitle' => implode(' · ', array_filter([$credential->project?->name, $credential->website?->name, $credential->login])),
            'url' => route('projects.show', $credential->project_id).'?tab=credentials',
            'icon' => 'fa-solid fa-key',
            'icon_bg' => self::ICON_TONES['amber'],
        ])->all());
    }

    /**
     * @return list<array<string, string>>
     */
    private function providers(): array
    {
        $query = Provider::query()->orderBy('name');
        $this->matches($query, ['name', 'code', 'contact_person', 'contact_email', 'account_manager', 'notes'], $this->terms);

        return $this->rank($query->limit(self::LIMIT)->get()->map(fn (Provider $provider) => [
            'title' => $provider->name,
            'subtitle' => implode(' · ', array_filter([strtoupper((string) $provider->type), $provider->account_manager])),
            'url' => route('tariffs.index'),
            'icon' => 'fa-solid fa-scale-balanced',
            'icon_bg' => self::ICON_TONES['indigo'],
        ])->all());
    }

    /**
     * @return list<array<string, string>>
     */
    private function users(): array
    {
        $query = User::query()->orderBy('name');
        $this->matches($query, ['name', 'email'], $this->terms);

        return $this->rank($query->limit(self::LIMIT)->get()->map(fn (User $user) => [
            'title' => $user->name,
            'subtitle' => $user->email.' | '.($user->telegram_id ? 'Telegram linked' : 'No linked Telegram'),
            'url' => route('users.index', ['search' => $user->email]),
            'icon' => 'fa-solid fa-user',
            'icon_bg' => self::ICON_TONES['indigo'],
        ])->all());
    }

    /**
     * @return list<array<string, string>>
     */
    private function posts(): array
    {
        $query = SmmPost::query()->orderByDesc('id');
        $this->matches($query, ['title', 'content'], $this->terms);

        return $this->rank($query->limit(self::LIMIT)->get()->map(fn (SmmPost $post) => [
            'title' => $post->title,
            'subtitle' => 'Platform: '.ucfirst((string) $post->platform).' | Status: '.ucfirst((string) $post->status),
            'url' => route('projects.show', $post->project_id).'?tab=operations',
            'icon' => 'fa-solid fa-paper-plane',
            'icon_bg' => self::ICON_TONES['pink'],
        ])->all());
    }

    /**
     * Every word has to match at least one of the columns ("rel.column" looks into a relation).
     *
     * @param  list<string>  $columns
     * @param  list<string>  $terms
     */
    private function matches(Builder $query, array $columns, array $terms, ?callable $extra = null): void
    {
        foreach ($terms as $term) {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

            $query->where(function (Builder $q) use ($columns, $pattern, $term, $extra) {
                foreach ($columns as $column) {
                    if (str_contains($column, '.')) {
                        [$relation, $field] = explode('.', $column, 2);
                        $q->orWhereHas($relation, fn (Builder $related) => $this->whereLike($related, $field, $pattern));
                    } else {
                        $this->whereLike($q, $column, $pattern, 'or');
                    }
                }

                if ($extra !== null) {
                    $extra($q, $term);
                }
            });
        }
    }

    private function whereLike(Builder $query, string $column, string $pattern, string $boolean = 'and'): void
    {
        $wrapped = $query->getQuery()->getGrammar()->wrap($column);
        $query->whereRaw("{$wrapped} {$this->like} ? escape '!'", [$pattern], $boolean);
    }

    private function anyTermIn(string $text): bool
    {
        foreach ($this->terms as $term) {
            if (mb_stripos($text, $term) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * A short plain-text snippet around the first matching word.
     */
    private function excerpt(string $text, int $radius = 70): string
    {
        $plain = trim((string) preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($text))));

        $position = null;
        foreach ($this->terms as $term) {
            $found = mb_stripos($plain, $term);
            if ($found !== false && ($position === null || $found < $position)) {
                $position = $found;
            }
        }

        if ($position === null) {
            return Str::limit($plain, $radius * 2);
        }

        $start = max(0, $position - (int) ($radius / 2));
        $snippet = mb_substr($plain, $start, $radius * 2);

        return ($start > 0 ? '…' : '').$snippet.($start + $radius * 2 < mb_strlen($plain) ? '…' : '');
    }

    /**
     * Whole-phrase hits in the title first.
     *
     * @param  list<array<string, string>>  $items
     * @return list<array<string, string>>
     */
    private function rank(array $items): array
    {
        $needle = trim((string) $this->query);

        usort($items, fn (array $a, array $b) => (mb_stripos($a['title'], $needle) === false ? 1 : 0) <=> (mb_stripos($b['title'], $needle) === false ? 1 : 0));

        return $items;
    }

    public function render()
    {
        return view('livewire.global-search');
    }
}
