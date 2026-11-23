<?php

namespace App\Livewire\Projects;

use App\Livewire\Concerns\EditsOnboardingTasks;
use App\Livewire\Concerns\RequiresPermission;
use App\Models\Project;
use App\Models\Task;
use App\Services\OnboardingBoard;
use Livewire\Component;

class OnboardingSection extends Component
{
    use EditsOnboardingTasks;
    use RequiresPermission;

    public Project $project;

    public function mount(Project $project): void
    {
        $this->requirePermission('view_tasks');

        $this->project = $project;
    }

    public function render(OnboardingBoard $board)
    {
        $row = $board->rowFor($this->project);
        $taskIds = $row === null ? collect() : $row['tasks']->pluck('id');

        return view('livewire.projects.onboarding-section', [
            'row' => $row,
            'statuses' => Task::ONBOARDING_STATUSES,
            'comments' => $row === null ? collect() : $board->comments($taskIds, 4),
            'history' => $row === null ? collect() : $board->history($taskIds, 8),
            'canEdit' => $this->canEditOnboarding(),
        ]);
    }
}
