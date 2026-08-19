<?php

namespace App\Services;

use App\Jobs\SendTelegramMessageJob;
use App\Mail\TaskAssignedMail;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Notify about task assignment.
     */
    public static function sendTaskAssigned(Task $task, User $assignee, ?User $actor = null, bool $isNew = false): void
    {
        $actorName = $actor ? $actor->name : 'System';
        $title = 'New Task Assigned';
        $message = "Task '{$task->title}' has been assigned to you by {$actorName}.";
        $url = route('tasks.kanban', ['task_id' => $task->id]);

        // 1. In-app notification
        $assignee->notify(new AppNotification($title, $message, $url, 'task_assigned'));

        // 2. Telegram notification
        if ($assignee->telegram_id && $assignee->getNotificationSetting('tg_notify_task_assigned', true)) {
            $escapedTitle = TelegramService::escapeMarkdownV2($task->title);

            $header = $isNew
                ? '📝 *A new task has been assigned to you:*'
                : '📝 *A task has been assigned to you:*';

            $text = "{$header}\n*Title:* {$escapedTitle}";

            SendTelegramMessageJob::dispatch($assignee->telegram_id, $text, self::getTelegramButtons($task));
        }

        // 3. Email notification
        if ($assignee->email) {
            try {
                Mail::to($assignee->email)->send(new TaskAssignedMail($task, $assignee, $actor));
            } catch (\Exception $e) {
                Log::error('Failed to send task assignment email: '.$e->getMessage());
            }
        }
    }

    /**
     * Notify about task creation.
     */
    public static function sendTaskCreated(Task $task, ?User $actor = null): void
    {
        $actorName = $actor ? $actor->name : 'System';
        $title = 'New Task Created';
        $message = "Task '{$task->title}' was created by {$actorName}.";
        $url = route('tasks.kanban', ['task_id' => $task->id]);

        if ($task->assigned_to && ($actor === null || $task->assigned_to !== $actor->id)) {
            $task->assignee?->notify(new AppNotification($title, $message, $url, 'task_created'));

            if ($task->assignee && $task->assignee->telegram_id && $task->assignee->getNotificationSetting('tg_notify_task_assigned', true)) {
                $escapedTitle = TelegramService::escapeMarkdownV2($task->title);
                $text = "📝 *New task created:*\n*Title:* {$escapedTitle}";

                SendTelegramMessageJob::dispatch($task->assignee->telegram_id, $text, self::getTelegramButtons($task));
            }
        }
    }

    /**
     * Notify about task status update.
     */
    public static function sendTaskStatusUpdated(Task $task, string $oldStatus, string $newStatus, ?User $actor = null): void
    {
        $actorName = $actor ? $actor->name : 'System';
        $readableStatus = str_replace('_', ' ', $newStatus);
        $title = 'Task Status Updated';
        $message = "Task '{$task->title}' status was changed to '{$readableStatus}' by {$actorName}.";
        $url = route('tasks.kanban', ['task_id' => $task->id]);

        // 1. In-app
        if ($task->assigned_to && ($actor === null || $task->assigned_to !== $actor->id)) {
            $task->assignee?->notify(new AppNotification($title, $message, $url, 'task_status_updated'));
        }

        // 2. Telegram
        if ($task->assignee && $task->assignee->telegram_id && $task->assignee->getNotificationSetting('tg_notify_task_status', true)) {
            $escapedTitle = TelegramService::escapeMarkdownV2($task->title);
            $escapedStatus = TelegramService::escapeMarkdownV2($readableStatus);

            $text = "🔄 *Task status updated:*\n*Task:* {$escapedTitle}\n*New Status:* {$escapedStatus}";

            SendTelegramMessageJob::dispatch($task->assignee->telegram_id, $text, self::getTelegramButtons($task));
        }
    }

    /**
     * Notify when task created via client portal.
     */
    public static function sendClientPortalTaskCreated(Task $task, Client $client, Project $company): void
    {
        $title = 'New Client Portal Request';
        $message = "New request from client '{$client->name}' ({$company->name}): '{$task->title}'.";
        $url = route('tasks.kanban', ['task_id' => $task->id]);

        // Send to admins or assigned manager
        $recipients = User::whereIn('id', function ($q) {
            $q->select('user_id')->from('activity_logs')->limit(10);
        })->get();

        if ($recipients->isEmpty()) {
            $recipients = User::all();
        }

        foreach ($recipients as $user) {
            $user->notify(new AppNotification($title, $message, $url, 'client_portal_task'));

            if ($user->telegram_id && $user->getNotificationSetting('tg_notify_client_portal', true)) {
                $escapedTitle = TelegramService::escapeMarkdownV2($task->title);
                $escapedCompany = TelegramService::escapeMarkdownV2($company->name);

                $text = "📥 *New Client Portal Request:*\n*Company:* {$escapedCompany}\n*Task:* {$escapedTitle}";

                SendTelegramMessageJob::dispatch($user->telegram_id, $text, self::getTelegramButtons($task));
            }
        }
    }

    /**
     * Notify when a comment is added to a task.
     */
    public static function sendNewCommentNotification($comment): void
    {
        $task = $comment->task;
        $actor = $comment->user;
        $actorName = $actor ? $actor->name : 'System';

        $title = 'New Comment on Task';
        $message = "{$actorName} commented on '{$task->title}': ".mb_substr($comment->content, 0, 50).'...';
        $url = route('tasks.kanban', ['task_id' => $task->id]);

        // Notify assignee if not the actor
        if ($task->assigned_to && ($actor === null || $task->assigned_to !== $actor->id)) {
            $task->assignee?->notify(new AppNotification($title, $message, $url, 'task_comment'));

            if ($task->assignee && $task->assignee->telegram_id && $task->assignee->getNotificationSetting('tg_notify_comments', true)) {
                $escapedTitle = TelegramService::escapeMarkdownV2($task->title);
                $escapedComment = TelegramService::escapeMarkdownV2(mb_substr($comment->content, 0, 100));

                $text = "💬 *New Comment on Task:*\n*Task:* {$escapedTitle}\n*Comment:* {$escapedComment}";

                SendTelegramMessageJob::dispatch($task->assignee->telegram_id, $text, self::getTelegramButtons($task));
            }
        }
    }

    /**
     * Notify about timer events.
     */
    public static function sendTimerAction(Task $task, string $actionType, int $durationSeconds, ?User $actor = null): void
    {
        if (! $task->creator_id || ($actor && $task->creator_id === $actor->id)) {
            return;
        }

        $creator = $task->creator;
        if (! $creator) {
            return;
        }
        $actorName = $actor ? $actor->name : 'System';
        $title = $actionType === 'started' ? 'Timer Started' : 'Timer Stopped';

        $durationString = $actionType === 'stopped' ? gmdate('H:i:s', $durationSeconds) : '';
        $message = $actionType === 'started'
            ? "{$actorName} started tracking time on task '{$task->title}'."
            : "{$actorName} stopped tracking time on task '{$task->title}' after {$durationString}.";

        $url = route('tasks.kanban', ['task_id' => $task->id]);

        // 1. In-app
        $creator->notify(new AppNotification($title, $message, $url, 'timer_action'));

        // 2. Telegram
        if ($creator->telegram_id && $creator->getNotificationSetting('tg_notify_timer_action', false)) {
            $escapedTitle = TelegramService::escapeMarkdownV2($task->title);
            $escapedActor = TelegramService::escapeMarkdownV2($actorName);
            $durationFormatted = gmdate('H:i:s', $durationSeconds);

            $text = $actionType === 'started'
                ? "⏱️ *Timer started by {$escapedActor}:*\n*Task:* {$escapedTitle}"
                : "⏱️ *Timer stopped by {$escapedActor} after working for {$durationFormatted}:*\n*Task:* {$escapedTitle}";

            SendTelegramMessageJob::dispatch($creator->telegram_id, $text, self::getTelegramButtons($task));
        }
    }

    /**
     * Notify Curator/Manager when a provider boarding is completed.
     */
    public static function sendProviderBoardingCompletedNotification(Project $project, string $providerName, User $manager, ?User $actor = null): void
    {
        $actorName = $actor ? $actor->name : 'System';
        $title = "🎉 Boarding Completed: {$project->name}";
        $message = "Boarding for provider '{$providerName}' has been marked as COMPLETED on company '{$project->name}' by {$actorName}.";
        $url = route('projects.show', $project->id).'?tab=boarding';

        // 1. In-app notification
        $manager->notify(new AppNotification($title, $message, $url, 'boarding_completed'));

        // 2. Telegram notification
        if ($manager->telegram_id) {
            $escapedProject = TelegramService::escapeMarkdownV2($project->name);
            $escapedProvider = TelegramService::escapeMarkdownV2($providerName);
            $escapedActor = TelegramService::escapeMarkdownV2($actorName);

            $text = "🎉 *Boarding Completed\\!*\n\n"
                  ."🏢 *Company:* {$escapedProject}\n"
                  ."💳 *Provider:* {$escapedProvider}\n"
                  ."👤 *Updated by:* {$escapedActor}";

            $buttons = [
                'inline_keyboard' => [
                    [
                        ['text' => '🏢 Open Company Boarding', 'url' => $url],
                    ],
                ],
            ];

            SendTelegramMessageJob::dispatch($manager->telegram_id, $text, $buttons);
        }
    }

    /**
     * Notify user when assigned as Curator/Manager for a company.
     */
    public static function sendCompanyManagerAssigned(Project $project, User $newManager, ?User $actor = null): void
    {
        $actorName = $actor ? $actor->name : 'System';
        $title = '🏢 Assigned as Company Manager';
        $message = "You have been assigned as Manager for company '{$project->name}' by {$actorName}.";
        $url = route('projects.show', $project->id);

        // 1. In-app notification
        $newManager->notify(new AppNotification($title, $message, $url, 'company_manager_assigned'));

        // 2. Telegram notification
        if ($newManager->telegram_id) {
            $escapedProject = TelegramService::escapeMarkdownV2($project->name);
            $escapedActor = TelegramService::escapeMarkdownV2($actorName);

            $text = "🏢 *Company Assigned\\!*\n\n"
                  ."You have been assigned as Manager for company *{$escapedProject}* by {$escapedActor}\\.";

            $buttons = [
                'inline_keyboard' => [
                    [
                        ['text' => '🏢 Open Company', 'url' => $url],
                    ],
                ],
            ];

            SendTelegramMessageJob::dispatch($newManager->telegram_id, $text, $buttons);
        }
    }

    /**
     * Helper to get standard task buttons for Telegram.
     */
    protected static function getTelegramButtons(Task $task): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '🔗 Open task', 'url' => route('tasks.kanban', ['task_id' => $task->id])],
                ],
            ],
        ];
    }
}
