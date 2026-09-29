<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected function getBotToken(): ?string
    {
        return config('services.telegram.bot_token')
            ?: (class_exists(Setting::class) ? Setting::get('telegram_bot_token') : null);
    }

    /**
     * Send message using Telegram Bot API
     */
    public function sendMessage(int|string $chatId, string $text, ?array $replyMarkup = null): bool
    {
        $botToken = $this->getBotToken();

        if (! $botToken) {
            Log::warning('Telegram Bot Token is not set in config or settings table.');

            return false;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'MarkdownV2',
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = $replyMarkup;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", $payload);

            if ($response->failed()) {
                Log::error('Telegram sendMessage failed', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Telegram sendMessage exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Handle incoming webhook or long polling update
     */
    public function handleUpdate(array $update): void
    {
        $message = $update['message'] ?? null;
        if (! $message) {
            return;
        }

        $chatId = $message['chat']['id'] ?? null;
        $fromId = $message['from']['id'] ?? null;
        $text = trim($message['text'] ?? '');
        $username = $message['from']['username'] ?? null;

        if (! $chatId || ! $text) {
            return;
        }

        // We check if the text is like /start <token>
        if (preg_match('/^\/start\s+(.+)$/i', $text, $matches)) {
            $token = trim($matches[1]);

            // Find user by token
            $user = User::where('tg_link_token', $token)->first();

            if ($user) {
                // Bind Telegram
                $user->update([
                    'telegram_id' => $fromId ?? $chatId,
                    'telegram_username' => $username,
                ]);

                $successText = "🎉 *Account successfully linked\\!*\n\nWelcome, ".self::escapeMarkdownV2($user->name).'\\! You will now receive important task and report notifications here\\.';
                $this->sendMessage($chatId, $successText);
            } else {
                $errorText = "❌ *Account linking error\\!*\n\nToken not found or invalid\\. Please go to your profile settings and click the link again\\.";
                $this->sendMessage($chatId, $errorText);
            }
        } elseif (preg_match('/^\/summary(@\w+)?$/i', $text)) {
            $user = User::where('telegram_id', (string) $fromId)->orWhere('telegram_id', (string) $chatId)->first();
            if (! $user) {
                $this->sendMessage($chatId, "❌ Account not linked\. Please link your account via Profile settings\.");

                return;
            }
            $this->sendSummary($chatId, $user);
        } elseif (strtolower($text) === '/help') {
            $helpText = "📖 *Available Commands:*\n\n"
                ."/summary \- Get your task summary\n"
                ."/help \- Show this help message\n\n"
                ."_Link your account via Profile settings to use commands\._ ";
            $this->sendMessage($chatId, $helpText);
        } else {
            // General response
            $helpText = "👋 *Hello\\!*\n\nThis bot is used to send notifications from CRM Compliance Hub\\.\n\nTo link your account, go to your *Profile* in the web interface and click the *Connect Telegram* button\\.";
            $this->sendMessage($chatId, $helpText);
        }
    }

    /**
     * Helper to escape MarkdownV2 special characters.
     * Special characters: _ * [ ] ( ) ~ ` > # + - = | { } . !
     */
    public static function escapeMarkdownV2(string $text): string
    {
        $chars = ['\\', '_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        $replace = array_map(fn ($c) => '\\'.$c, $chars);

        return str_replace($chars, $replace, $text);
    }

    /**
     * Helper to escape URLs for MarkdownV2 inline links [text](url).
     */
    public static function escapeMarkdownV2Url(string $url): string
    {
        return str_replace(['\\', ')'], ['\\\\', '\\)'], $url);
    }

    /**
     * Build the MarkdownV2 text for user daily / task summary.
     */
    public function buildSummaryText(User $user, ?string $titleHeader = null): string
    {
        $name = self::escapeMarkdownV2($user->name);
        $header = $titleHeader ? self::escapeMarkdownV2($titleHeader) : "Daily Summary for {$name}";

        $text = "📋 *{$header}*\n\n";

        // Active timer if any
        $activeTimer = TaskTimeLog::where('user_id', $user->id)
            ->whereNull('stopped_at')
            ->with('task')
            ->first();

        if ($activeTimer && $activeTimer->task) {
            $timerTitle = self::escapeMarkdownV2($activeTimer->task->title);
            $timerUrl = self::escapeMarkdownV2Url(route('tasks.kanban', ['task_id' => $activeTimer->task->id]));
            $elapsed = gmdate('H:i:s', $activeTimer->started_at->diffInSeconds(now(), true));
            $elapsed = self::escapeMarkdownV2($elapsed);
            $text .= "⏱ *Active Timer:* [{$timerTitle}]({$timerUrl}) \({$elapsed}\)\n\n";
        }

        $activeTasksQuery = Task::assignedToUser($user->id)
            ->whereNotIn('status', ['done']);

        $totalActive = (clone $activeTasksQuery)->count();

        $overdueCount = (clone $activeTasksQuery)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->count();

        $dueTodayCount = (clone $activeTasksQuery)
            ->whereDate('due_date', today())
            ->count();

        // Stats section
        $text .= "📊 *Stats:*\n";
        $text .= "• Active tasks: *{$totalActive}*\n";
        if ($overdueCount > 0) {
            $text .= "• ⚠️ Overdue: *{$overdueCount}*\n";
        }
        if ($dueTodayCount > 0) {
            $text .= "• 📅 Due today: *{$dueTodayCount}*\n";
        }
        $text .= "\n";

        // Tasks list section
        if ($totalActive === 0) {
            $text .= "✅ No active tasks\. Great work\!\n";
        } else {
            $text .= "📝 *Your Tasks:*\n";
            $tasks = (clone $activeTasksQuery)
                ->with('project')
                ->orderByRaw("CASE status WHEN 'in_progress' THEN 1 WHEN 'review' THEN 2 WHEN 'todo' THEN 3 ELSE 4 END")
                ->orderByRaw('CASE WHEN due_date IS NOT NULL AND due_date < ? THEN 0 WHEN due_date IS NOT NULL AND DATE(due_date) = ? THEN 1 ELSE 2 END', [now()->startOfDay()->toDateTimeString(), today()->toDateString()])
                ->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
                ->limit(8)
                ->get();

            foreach ($tasks as $task) {
                $statusEmoji = match ($task->status) {
                    'in_progress' => '🔵',
                    'review' => '🟡',
                    'todo' => '⚪',
                    'done' => '🟢',
                    default => '⚪',
                };
                $priorityEmoji = match ($task->priority) {
                    'critical' => '🔴',
                    'high' => '🟠',
                    'medium' => '🟢',
                    'low' => '⚪',
                    default => '🟢',
                };

                $taskTitle = self::escapeMarkdownV2($task->title);
                $taskUrl = self::escapeMarkdownV2Url(route('tasks.kanban', ['task_id' => $task->id]));
                $statusLabel = self::escapeMarkdownV2(str_replace('_', ' ', $task->status));

                $deadlineBadge = '';
                if ($task->due_date) {
                    if ($task->due_date->lt(now()->startOfDay())) {
                        $deadlineBadge = ' ⚠️';
                    } elseif ($task->due_date->isToday()) {
                        $deadlineBadge = ' 📅';
                    }
                }

                $text .= "{$statusEmoji} {$priorityEmoji} [{$taskTitle}]({$taskUrl}) _{$statusLabel}_{$deadlineBadge}\n";
            }

            if ($totalActive > 8) {
                $remaining = $totalActive - 8;
                $text .= "_\.\.\. and {$remaining} more_\n";
            }
        }

        // Recent comments (last 24 hours on user's assigned tasks by others or clients)
        $recentComments = Comment::whereHas('task', function ($q) use ($user) {
            $q->assignedToUser($user->id);
        })
            ->where(function ($q) use ($user) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', '!=', $user->id)
                    ->orWhereNotNull('client_id');
            })
            ->where('created_at', '>=', now()->subHours(24))
            ->with(['user', 'client', 'task'])
            ->latest()
            ->limit(3)
            ->get();

        if ($recentComments->isNotEmpty()) {
            $text .= "\n💬 *Recent Comments:*\n";
            foreach ($recentComments as $comment) {
                $authorName = $comment->user?->name ?? ($comment->client?->name ?? 'System');
                $taskTitle = $comment->task?->title ?? 'Task';
                $taskUrl = self::escapeMarkdownV2Url(route('tasks.kanban', ['task_id' => $comment->task_id]));
                $shortContent = mb_strimwidth(trim(preg_replace('/\s+/', ' ', strip_tags($comment->content))), 0, 45, '…');
                $timeAgo = $comment->created_at->diffForHumans(short: true);

                $escapedAuthor = self::escapeMarkdownV2($authorName);
                $escapedTask = self::escapeMarkdownV2($taskTitle);
                $escapedContent = self::escapeMarkdownV2($shortContent);
                $escapedTime = self::escapeMarkdownV2($timeAgo);

                $text .= "• [{$escapedTask}]({$taskUrl}) \— {$escapedAuthor}: _{$escapedContent}_ \({$escapedTime}\)\n";
            }
        }

        return trim($text);
    }

    public function sendSummary(int|string $chatId, User $user, ?string $titleHeader = null): bool
    {
        $text = $this->buildSummaryText($user, $titleHeader);

        $buttons = [
            'inline_keyboard' => [
                [
                    ['text' => '📋 Открыть My Work / Задачи', 'url' => route('tasks.kanban')],
                ],
            ],
        ];

        return $this->sendMessage($chatId, $text, $buttons);
    }
}
