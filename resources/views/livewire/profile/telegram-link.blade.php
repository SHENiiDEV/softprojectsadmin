<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Volt\Component;

new class extends Component
{
    public ?string $telegramId = null;
    public ?string $telegramUsername = null;
    public ?string $tgLinkToken = null;
    public string $botUsername = '';

    public bool $tgNotifyTaskAssigned = true;
    public bool $tgNotifyTaskStatusUpdated = true;
    public bool $tgNotifyTaskCreated = false;
    public bool $tgNotifyTimerAction = false;
    public bool $tgNotifyNewComment = true;

    public function mount(): void
    {
        $user = Auth::user();
        $this->botUsername = config('services.telegram.bot_username', 'pm_compliance_bot');
        
        if (!$user->tg_link_token) {
            $user->tg_link_token = Str::random(32);
            $user->save();
        }

        $this->telegramId = $user->telegram_id;
        $this->telegramUsername = $user->telegram_username;
        $this->tgLinkToken = $user->tg_link_token;

        $this->tgNotifyTaskAssigned = $user->getNotificationSetting('tg_notify_task_assigned', true);
        $this->tgNotifyTaskStatusUpdated = $user->getNotificationSetting('tg_notify_task_status_updated', true);
        $this->tgNotifyTaskCreated = $user->getNotificationSetting('tg_notify_task_created', false);
        $this->tgNotifyTimerAction = $user->getNotificationSetting('tg_notify_timer_action', false);
        $this->tgNotifyNewComment = $user->getNotificationSetting('tg_notify_new_comment', true);
    }

    public function saveSettings(): void
    {
        $user = Auth::user();
        $settings = $user->notification_settings ?? [];
        
        $settings['tg_notify_task_assigned'] = $this->tgNotifyTaskAssigned;
        $settings['tg_notify_task_status_updated'] = $this->tgNotifyTaskStatusUpdated;
        $settings['tg_notify_task_created'] = $this->tgNotifyTaskCreated;
        $settings['tg_notify_timer_action'] = $this->tgNotifyTimerAction;
        $settings['tg_notify_new_comment'] = $this->tgNotifyNewComment;
        
        $user->notification_settings = $settings;
        $user->save();

        session()->flash('status', 'settings-updated');
    }

    public function unlink(): void
    {
        $user = Auth::user();
        $user->telegram_id = null;
        $user->telegram_username = null;
        $user->tg_link_token = Str::random(32);
        $user->save();

        $this->telegramId = null;
        $this->telegramUsername = null;
        $this->tgLinkToken = $user->tg_link_token;

        session()->flash('status', 'telegram-unlinked');
    }
}; ?>

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Telegram Integration') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Link your Telegram account to receive instant notifications about assigned tasks and deadlines.') }}
        </p>
    </header>

    @if (session('status') === 'telegram-unlinked')
        <div class="p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300" role="alert">
            {{ __('Telegram account successfully unlinked.') }}
        </div>
    @endif

    <div class="space-y-4">
        @if ($telegramId)
            <div class="flex items-center space-x-3 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 p-4 rounded-lg">
                <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1">
                    <span class="text-sm font-semibold text-green-800 dark:text-green-300">
                        {{ __('Connected') }}
                    </span>
                    <p class="text-xs text-green-600 dark:text-green-400 mt-0.5">
                        {{ __('Telegram Username:') }} @if($telegramUsername) <strong>{{ '@' . $telegramUsername }}</strong> @else <strong>ID: {{ $telegramId }}</strong> @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <x-danger-button wire:click="unlink" wire:confirm="{{ __('Are you sure you want to unlink Telegram?') }}">
                    {{ __('Unlink Telegram') }}
                </x-danger-button>
            </div>

            <!-- Telegram Notification Preferences -->
            <div class="mt-6 border-t border-gray-250 dark:border-gray-700 pt-6 space-y-4">
                <h3 class="text-md font-medium text-gray-900 dark:text-gray-100">
                    {{ __('Telegram Notification Preferences') }}
                </h3>
                <p class="text-xs text-gray-650 dark:text-gray-400">
                    {{ __('Configure which events will trigger instant messages to your linked Telegram account.') }}
                </p>

                @if (session('status') === 'settings-updated')
                    <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-green-950/20 dark:text-green-300 border border-green-200 dark:border-green-800" role="alert">
                        {{ __('Notification preferences saved successfully.') }}
                    </div>
                @endif

                <div class="space-y-4">
                    <label class="flex items-start">
                        <input type="checkbox" wire:model="tgNotifyTaskAssigned" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800 mt-1">
                        <span class="ms-3 text-sm text-gray-600 dark:text-gray-400">
                            <strong class="block font-medium text-gray-800 dark:text-gray-200">{{ __('Task Assigned to You') }}</strong>
                            <span class="text-xs">{{ __('Get notified when a manager or teammate assigns a task to you.') }}</span>
                        </span>
                    </label>

                    <label class="flex items-start">
                        <input type="checkbox" wire:model="tgNotifyTaskStatusUpdated" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800 mt-1">
                        <span class="ms-3 text-sm text-gray-600 dark:text-gray-400">
                            <strong class="block font-medium text-gray-800 dark:text-gray-200">{{ __('Task Status Updates') }}</strong>
                            <span class="text-xs">{{ __('Get notified when a task you are assigned to (or created) changes status.') }}</span>
                        </span>
                    </label>

                    @hasanyrole('admin|manager|curator')
                    <label class="flex items-start">
                        <input type="checkbox" wire:model="tgNotifyTaskCreated" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800 mt-1">
                        <span class="ms-3 text-sm text-gray-600 dark:text-gray-400">
                            <strong class="block font-medium text-gray-800 dark:text-gray-200">{{ __('New Tasks / Support Tickets') }}</strong>
                            <span class="text-xs">{{ __('Get notified when a new task is created by teammates or a support ticket is submitted via the Client Portal.') }}</span>
                        </span>
                    </label>
                    @endhasanyrole

                    <label class="flex items-start">
                        <input type="checkbox" wire:model="tgNotifyTimerAction" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800 mt-1">
                        <span class="ms-3 text-sm text-gray-600 dark:text-gray-400">
                            <strong class="block font-medium text-gray-800 dark:text-gray-200">{{ __('Timer Actions on Your Tasks') }}</strong>
                            <span class="text-xs">{{ __('Get notified when someone starts or stops a tracking timer on tasks created by you.') }}</span>
                        </span>
                    </label>

                    <label class="flex items-start">
                        <input type="checkbox" wire:model="tgNotifyNewComment" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800 mt-1">
                        <span class="ms-3 text-sm text-gray-600 dark:text-gray-400">
                            <strong class="block font-medium text-gray-800 dark:text-gray-200">{{ __('New Comments / Mentions') }}</strong>
                            <span class="text-xs">{{ __('Get notified when someone comments on your tasks or mentions you with @username.') }}</span>
                        </span>
                    </label>
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <x-primary-button wire:click="saveSettings">
                        {{ __('Save Preferences') }}
                    </x-primary-button>
                </div>
            </div>
        @else
            <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4 rounded-lg space-y-3">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    {{ __('To link your account, go to our bot and start it with your personal token.') }}
                </p>
                
                <div class="flex items-center space-x-2 bg-white dark:bg-gray-800 p-2 border border-gray-200 dark:border-gray-700 rounded text-xs select-all font-mono text-gray-600 dark:text-gray-400">
                    <span class="font-bold text-gray-950 dark:text-gray-100">Token:</span>
                    <span>{{ $tgLinkToken }}</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <a href="https://t.me/{{ $botUsername }}?start={{ $tgLinkToken }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 me-2 fill-current" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15.82-1.07 4.79-1.54 7.28-.2.95-.55 1.27-.88 1.3-.73.07-1.29-.48-2-.94-1.11-.73-1.74-1.18-2.82-1.89-1.25-.82-.44-1.27.27-2.01.19-.19 3.42-3.14 3.48-3.41.01-.03.01-.15-.06-.21-.07-.06-.17-.04-.25-.02-.11.02-1.82 1.15-5.14 3.4-.49.33-.93.5-1.33.49-.44 0-1.29-.24-1.92-.44-.77-.25-1.39-.39-1.34-.82.03-.22.33-.45.9-.69 3.51-1.52 5.86-2.53 7.03-3.02 3.35-1.39 4.05-1.63 4.51-1.64.1 0 .33.02.48.15.12.1.16.24.18.34.02.13.01.27-.01.42z"/>
                    </svg>
                    {{ __('Connect Telegram') }}
                </a>
            </div>
        @endif
    </div>
</section>
