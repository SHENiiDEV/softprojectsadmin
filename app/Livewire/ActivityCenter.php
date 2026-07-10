<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;

class ActivityCenter extends Component
{
    public Collection $activities;
    public Collection $projects;
    public Collection $users;

    public $filterProject = null;
    public $filterUser    = null;
    public $filterType    = null;

    public function mount(): void
    {
        $this->activities = collect();
        $this->projects   = Project::orderBy('name')->get(['id', 'name']);
        $this->users      = User::orderBy('name')->get(['id', 'name']);
        $this->loadActivities();
    }

    public function updatedFilterProject(): void { $this->loadActivities(); }
    public function updatedFilterUser(): void    { $this->loadActivities(); }
    public function updatedFilterType(): void    { $this->loadActivities(); }

    protected function loadActivities(): void
    {
        $query = ActivityLog::query()
            ->with(['project', 'client', 'task', 'user']);

        if ($this->filterProject) {
            $query->where('project_id', $this->filterProject);
        }
        if ($this->filterUser) {
            $query->where('user_id', $this->filterUser);
        }
        if ($this->filterType) {
            $query->where('action', $this->filterType);
        }

        $this->activities = $query->latest()->take(200)->get();
    }

    public function render()
    {
        return view('livewire.activity-center');
    }
}
