<?php

namespace App\Livewire\Concerns;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Services\OnboardingBoard;
use Illuminate\Support\Str;

/**
 * Inline editing of the onboarding status / current status of Onboarding and KYB tasks.
 * Follows the Kanban rules: manage_tasks, and curators may not edit tasks.
 */
trait EditsOnboardingTasks
{
    protected function canEditOnboarding(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('manage_tasks') && ! $user->hasRole('curator');
    }

    private function authorizeOnboardingEdit(): void
    {
        abort_unless($this->canEditOnboarding(), 403);
    }

    private function onboardingTask(int $taskId): Task
    {
        return Task::onboarding()->findOrFail($taskId);
    }

    public function setStatus(int $taskId, string $status): void
    {
        $this->authorizeOnboardingEdit();

        if (! in_array($status, Task::ONBOARDING_STATUSES, true)) {
            return;
        }

        $this->onboardingTask($taskId)->update(['onboarding_status' => $status]);
        session()->flash('message', 'Onboarding status updated.');
    }

    public function saveCurrentStatus(int $taskId, string $text): void
    {
        $this->authorizeOnboardingEdit();

        $this->onboardingTask($taskId)->update(['current_status' => trim($text) !== '' ? Str::limit(trim($text), 5000, '') : null]);
        session()->flash('message', 'Current status saved.');
    }

    /**
     * Change the status of one provider for a company that is in the onboarding tracker.
     */
    public function setProviderStatus(int $projectId, string $provider, string $status): void
    {
        $this->authorizeOnboardingEdit();

        $labels = OnboardingBoard::PROVIDER_STATUSES;
        $provider = trim($provider);

        if ($provider === '' || ! isset($labels[$status])) {
            return;
        }

        $project = Project::findOrFail($projectId);
        $task = Task::onboarding()->where('project_id', $project->id)->orderByDesc('id')->first();
        abort_if($task === null, 404);

        $from = app(OnboardingBoard::class)->setProviderStatus($project, $provider, $status);

        if ($from !== $labels[$status]) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'task_id' => $task->id,
                'project_id' => $project->id,
                'action' => 'provider_status_updated',
                'description' => "Provider {$provider}: status changed from '{$from}' to '{$labels[$status]}'",
            ]);
        }

        session()->flash('message', "{$provider}: {$labels[$status]}.");
    }
}
