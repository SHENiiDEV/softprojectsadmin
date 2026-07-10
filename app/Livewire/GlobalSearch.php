<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\Project;
use App\Models\User;
use App\Models\Website;
use App\Models\Credential;

class GlobalSearch extends Component
{
    public $query = '';
    public $results = [];

    public function updatedQuery()
    {
        $this->results = [];
        if (strlen($this->query) < 2) {
            return;
        }
        // Clients
        $clients = \App\Models\Client::where('name', 'ilike', "%{$this->query}%")
            ->limit(5)
            ->get(['id', 'name']);
        foreach ($clients as $c) {
            $this->results[] = [
                'type' => 'client',
                'title' => $c->name,
                'url' => route('clients.index') . '?search=' . urlencode($c->name),
                'icon' => 'fa-solid fa-users',
            ];
        }

        // Companies (Projects)
        $projects = Project::where('name', 'ilike', "%{$this->query}%")
            ->limit(5)
            ->get(['id', 'name']);
        foreach ($projects as $p) {
            $this->results[] = [
                'type' => 'company',
                'title' => $p->name,
                'url' => route('projects.show', $p->id),
                'icon' => 'fa-solid fa-building',
            ];
        }
        // Tasks
        $tasks = \App\Models\Task::where('title', 'ilike', "%{$this->query}%")
            ->limit(5)
            ->get(['id', 'title']);
        foreach ($tasks as $t) {
            $this->results[] = [
                'type' => 'task',
                'title' => $t->title,
                'url' => route('tasks.kanban', ['task_id' => $t->id]),
                'icon' => 'fa-solid fa-check-square',
            ];
        }
        // Users
        $users = User::where('name', 'ilike', "%{$this->query}%")
            ->orWhere('email', 'ilike', "%{$this->query}%")
            ->limit(5)
            ->get(['id', 'name', 'email']);
        foreach ($users as $u) {
            $this->results[] = [
                'type' => 'user',
                'title' => $u->name . ' (' . $u->email . ')',
                'url' => route('users.index') . '?edit=' . $u->id,
                'icon' => 'fa-solid fa-user',
            ];
        }
        // Websites
        $websites = Website::where('name', 'ilike', "%{$this->query}%")
            ->orWhere('url', 'ilike', "%{$this->query}%")
            ->limit(5)
            ->get(['id', 'name', 'project_id']);
        foreach ($websites as $w) {
            $this->results[] = [
                'type'  => 'website',
                'title' => $w->name,
                'url'   => route('projects.show', $w->project_id),
                'icon'  => 'fa-solid fa-globe',
            ];
        }
        // Credentials
        $credentials = Credential::where('name', 'ilike', "%{$this->query}%")
            ->orWhere('username', 'ilike', "%{$this->query}%")
            ->limit(5)
            ->get(['id', 'name', 'project_id']);
        foreach ($credentials as $c) {
            $this->results[] = [
                'type'  => 'credential',
                'title' => $c->name,
                'url'   => route('projects.show', $c->project_id) . '?tab=credentials',
                'icon'  => 'fa-solid fa-key',
            ];
        }

        // Project Notes
        if (config('features.global_search_notes', true)) {
            $notes = \App\Models\ProjectNote::where('content', 'ilike', "%{$this->query}%")
                ->limit(5)
                ->with('project')
                ->get();
            foreach ($notes as $n) {
                $projectName = $n->project ? $n->project->name : 'Unknown';
                $this->results[] = [
                    'type'  => 'note',
                    'title' => 'Note in ' . $projectName . ': ' . \Illuminate\Support\Str::limit(strip_tags($n->content), 30),
                    'url'   => route('projects.show', $n->project_id) . '?tab=notes',
                    'icon'  => 'fa-solid fa-sticky-note',
                ];
            }
        }
    }

    public function render()
    {
        return view('livewire.global-search');
    }
}
?>
