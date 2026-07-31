<?php

namespace App\Services;

use App\Jobs\SendTelegramMessageJob;
use App\Mail\TaskAssignedMail;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
     * Notify about task status update.
     */
    public static function sendTaskStatusUpdated(Task $task, string $oldStatus, string $newStatus, ?User $actor = null): void
    {
        $actorName = $actor ? $actor->name : 'System';
        $readableStatus = str_replace('_', ' ', $newStatus);
        $title = 'Task Status Updated';
        $message = "Task '{$task->title}' status was changed to '{$readableStatus}' by {$actorName}.";
        $url = route('tasks.kanban', ['task_id' => $task->id]);

        // Determine recipients (assignee and creator if different from actor)
        $recipients = collect();
        if ($task->assigned_to && (! $actor || $task->assigned_to !== $actor->id)) {
            $recipients->push($task->assignee);
        }
        if ($task->creator_id && (! $actor || $task->creator_id !== $actor->id) && $task->creator_id !== $task->assigned_to) {
            $recipients->push($task->creator);
        }

        foreach ($recipients as $user) {
            // 1. In-app
            $user->notify(new AppNotification($title, $message, $url, 'task_status_updated'));

            // 2. Telegram
            if ($user->telegram_id && $user->getNotificationSetting('tg_notify_task_status_updated', true)) {
                $escapedTitle = TelegramService::escapeMarkdownV2($task->title);
                $escapedStatus = TelegramService::escapeMarkdownV2($readableStatus);
                $escapedActor = TelegramService::escapeMarkdownV2($actorName);
                $text = "🔄 *Task status updated to '{$escapedStatus}' by {$escapedActor}:*\n*Title:* {$escapedTitle}";

                SendTelegramMessageJob::dispatch($user->telegram_id, $text, self::getTelegramButtons($task));
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

        $usersQuery = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'manager', 'curator']);
        });

        if ($actor) {
            $usersQuery->where('id', '!=', $actor->id);
        }

        $users = $usersQuery->get();

        foreach ($users as $user) {
            // Only send if they explicitly turned on tg_notify_task_created (default false)
            if ($user->telegram_id && $user->getNotificationSetting('tg_notify_task_created', false)) {
                $escapedTitle = TelegramService::escapeMarkdownV2($task->title);
                $escapedActor = TelegramService::escapeMarkdownV2($actorName);
                $text = "🆕 *New task created by {$escapedActor}:*\n*Title:* {$escapedTitle}";

                SendTelegramMessageJob::dispatch($user->telegram_id, $text, self::getTelegramButtons($task));
            }
        }
    }

    /**
     * Notify about client portal task creation.
     */
    public static function sendClientPortalTaskCreated(Task $task, Client $client, Project $company): void
    {
        $title = 'New Support Ticket';
        $message = "Client '{$client->name}' submitted a support ticket: '{$task->title}' for company '{$company->name}'.";
        $url = route('tasks.kanban', ['task_id' => $task->id]);

        // Support tickets go to curators and managers, plus admins
        $users = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'manager', 'curator']);
        })->get();

        foreach ($users as $user) {
            // 1. In-app
            $user->notify(new AppNotification($title, $message, $url, 'client_portal_task_created'));

            // 2. Telegram (default true for client portal task updates)
            if ($user->telegram_id && $user->getNotificationSetting('tg_notify_task_created', true)) {
                $escapedTitle = TelegramService::escapeMarkdownV2($task->title);
                $escapedClient = TelegramService::escapeMarkdownV2($client->name);
                $escapedCompany = TelegramService::escapeMarkdownV2($company->name);
                $text = "🌐 *New support ticket from client {$escapedClient} ({$escapedCompany}):*\n*Title:* {$escapedTitle}";

                SendTelegramMessageJob::dispatch($user->telegram_id, $text, self::getTelegramButtons($task));
            }
        }
    }

    /**
     * Notify about new task comment.
     */
    public static function sendNewCommentNotification(Comment $comment): void
    {
        $comment->load('task', 'user', 'client', 'project');
        $task = $comment->task;
        $project = $comment->project ?: ($task ? $task->project : null);

        if (! $task && ! $project) {
            return;
        }

        $authorName = 'System';
        if ($comment->user) {
            $authorName = $comment->user->name;
        } elseif ($comment->client) {
            $authorName = $comment->client->name.' (Client)';
        }

        $title = $task ? 'New Task Comment' : 'New Company Comment';
        $contextName = $task ? $task->title : ($project ? $project->name : 'Company');
        $message = $task
            ? "New comment by {$authorName} on task '{$task->title}': \"".Str::limit(strip_tags($comment->content), 50).'"'
            : "New comment by {$authorName} on company '{$project->name}': \"".Str::limit(strip_tags($comment->content), 50).'"';

        $url = $task
            ? route('tasks.kanban', ['task_id' => $task->id])
            : ($project ? route('projects.show', $project->id).'?tab=comments' : '#');

        $recipients = collect();

        if ($task) {
            // 1. Task Assignee
            if ($task->assigned_to) {
                $isAuthor = ($comment->user_id && $task->assigned_to === $comment->user_id);
                if (! $isAuthor) {
                    $recipients->put($task->assigned_to, $task->assignee);
                }
            }

            // 2. Task Creator
            if ($task->creator_id && ! $recipients->has($task->creator_id)) {
                $isAuthor = ($comment->user_id && $task->creator_id === $comment->user_id);
                if (! $isAuthor) {
                    $recipients->put($task->creator_id, $task->creator);
                }
            }
        } elseif ($project) {
            // 1. Project Manager
            if ($project->manager_id) {
                $isAuthor = ($comment->user_id && $project->manager_id === $comment->user_id);
                if (! $isAuthor) {
                    $recipients->put($project->manager_id, $project->manager);
                }
            }
        }

        // 3. Mentioned Users
        $mentionedUsers = $comment->getMentionedUsers();
        foreach ($mentionedUsers as $mentionedUser) {
            if ($mentionedUser->id !== $comment->user_id && ! $recipients->has($mentionedUser->id)) {
                $recipients->put($mentionedUser->id, $mentionedUser);
            }
        }

        foreach ($recipients as $user) {
            $user->notify(new AppNotification($title, $message, $url, 'new_comment'));

            if ($user->telegram_id && $user->getNotificationSetting('tg_notify_new_comment', true)) {
                $escapedContext = TelegramService::escapeMarkdownV2($contextName);
                $escapedAuthor = TelegramService::escapeMarkdownV2($authorName);
                $escapedContent = TelegramService::escapeMarkdownV2($comment->content);

                $privatePrefix = $comment->is_private ? '🔒 *[Private]* ' : '';

                $text = $task
                    ? "💬 {$privatePrefix}*New comment by {$escapedAuthor} on:*\n*Task:* {$escapedContext}\n\n*Comment:* {$escapedContent}"
                    : "💬 {$privatePrefix}*New comment by {$escapedAuthor} on:*\n*Company:* {$escapedContext}\n\n*Comment:* {$escapedContent}";

                $buttons = $task
                    ? self::getTelegramButtons($task)
                    : [
                        'inline_keyboard' => [
                            [
                                ['text' => '🔗 Open company', 'url' => route('projects.show', $project->id).'?tab=comments'],
                            ],
                        ],
                    ];

                SendTelegramMessageJob::dispatch($user->telegram_id, $text, $buttons);
            }
        }
    }

    /**
     * Notify about timer events.
     */
    public static function sendTimerAction(Task $task, string $actionType, int $durationSeconds, ?User $actor = null): void
    {
        // Notify the creator of the task when a worker starts/stops a timer on it (if they want)
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
