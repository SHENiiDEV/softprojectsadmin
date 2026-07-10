<?php

namespace App\Livewire\Projects;

use App\Models\ActivityLog;
use App\Models\Project;
use Livewire\Component;

class ChangelogSection extends Component
{
    public Project $project;

    public function render()
    {
        $activities = ActivityLog::where('project_id', $this->project->id)
            ->with(['user', 'client'])
            ->latest()
            ->get();

        return view('livewire.projects.changelog-section', compact('activities'));
    }
}
