<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public array $settings = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->settings = $user->notification_settings ?? [
            'email_notify_task_assigned' => true,
            'email_notify_task_status_updated' => true,
            'tg_notify_task_assigned' => true,
            'tg_notify_task_status_updated' => true,
            'tg_notify_task_created' => false,
            'tg_notify_new_comment' => true,
            'tg_notify_timer_action' => false,
        ];
    }

    /**
     * Save settings to DB.
     */
    public function updateNotificationSettings(): void
    {
        $user = Auth::user();
        
        // Ensure values are cast properly to boolean
        $updatedSettings = [];
        foreach ($this->settings as $key => $val) {
            $updatedSettings[$key] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
        }

        $user->update([
            'notification_settings' => $updatedSettings,
        ]);

        $this->dispatch('notifications-updated');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Notification Preferences') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Manage where and how you want to receive project alerts.') }}
        </p>
    </header>

    <form wire:submit="updateNotificationSettings" class="mt-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/50 dark:bg-slate-900/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80">
            <!-- Email notifications -->
            <div class="space-y-4">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 border-b border-slate-100 dark:border-slate-800/40 pb-2 flex items-center">
                    <i class="fa-solid fa-envelope text-sky-500 mr-2"></i> Email Notifications
                </h3>
                <div class="space-y-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="settings.email_notify_task_assigned" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500 mt-1">
                        <div class="text-sm">
                            <span class="font-medium text-slate-700 dark:text-slate-300">Task Assigned</span>
                            <p class="text-xs text-slate-500">Receive an email when a new task is assigned to you.</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="settings.email_notify_task_status_updated" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500 mt-1">
                        <div class="text-sm">
                            <span class="font-medium text-slate-700 dark:text-slate-300">Status Updates</span>
                            <p class="text-xs text-slate-500">Receive an email when status of your tasks changes.</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Telegram Notifications -->
            <div class="space-y-4">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 border-b border-slate-100 dark:border-slate-800/40 pb-2 flex items-center">
                    <i class="fa-brands fa-telegram text-sky-500 mr-2"></i> Telegram Alerts
                </h3>
                <div class="space-y-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="settings.tg_notify_task_assigned" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500 mt-1">
                        <div class="text-sm">
                            <span class="font-medium text-slate-700 dark:text-slate-300">Task Assigned</span>
                            <p class="text-xs text-slate-500">Get a Telegram ping when assigned a new task.</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="settings.tg_notify_task_status_updated" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500 mt-1">
                        <div class="text-sm">
                            <span class="font-medium text-slate-700 dark:text-slate-300">Status Updates</span>
                            <p class="text-xs text-slate-500">Get a Telegram update on task transitions.</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="settings.tg_notify_task_created" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500 mt-1">
                        <div class="text-sm">
                            <span class="font-medium text-slate-700 dark:text-slate-300">Task Created</span>
                            <p class="text-xs text-slate-500">Get notified when any user creates a new task.</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="settings.tg_notify_new_comment" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500 mt-1">
                        <div class="text-sm">
                            <span class="font-medium text-slate-700 dark:text-slate-300">Comments</span>
                            <p class="text-xs text-slate-500">Receive alerts when someone leaves a comment on your tasks.</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="settings.tg_notify_timer_action" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500 mt-1">
                        <div class="text-sm">
                            <span class="font-medium text-slate-700 dark:text-slate-300">Timer Activity</span>
                            <p class="text-xs text-slate-500">Get a Telegram message when someone starts/stops a timer on your tasks.</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button class="rounded-xl">{{ __('Save Settings') }}</x-primary-button>
            <x-action-message class="me-3" on="notifications-updated">
                {{ __('Preferences saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
