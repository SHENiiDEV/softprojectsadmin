<?php

namespace App\Livewire\Concerns;

use App\Models\Task;
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
}
