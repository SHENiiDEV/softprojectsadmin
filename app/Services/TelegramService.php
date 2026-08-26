<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected ?string $botToken;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
    }

    /**
     * Send message using Telegram Bot API
     */
    public function sendMessage(int|string $chatId, string $text, ?array $replyMarkup = null): bool
    {
        if (! $this->botToken) {
            Log::warning('Telegram Bot Token is not set.');

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
            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", $payload);

            if ($response->failed()) {
                Log::error('Telegram sendMessage failed', [
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
        } elseif (strtolower($text) === '/summary') {
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
     * Note: We escape these: _ * [ ] ( ) ~ ` > # + - = | { } . !
     */
    public static function escapeMarkdownV2(string $text): string
    {
        $chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        $replace = array_map(fn ($c) => '\\'.$c, $chars);

        return str_replace($chars, $replace, $text);
    }

    private function sendSummary(int|string $chatId, User $user): void
    {
        $tasks = Task::where('assigned_to', $user->id)
            ->whereNotIn('status', ['done'])
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 1 WHEN 'review' THEN 2 WHEN 'todo' THEN 3 ELSE 4 END")
            ->limit(10)
            ->get();

        $overdueTasks = Task::where('assigned_to', $user->id)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->whereNotIn('status', ['done'])
            ->count();

        $todayDeadlines = Task::where('assigned_to', $user->id)
            ->whereDate('due_date', today())
            ->whereNotIn('status', ['done'])
            ->count();

        $activeTimer = TaskTimeLog::where('user_id', $user->id)
            ->whereNull('stopped_at')
            ->with('task')
            ->first();

        $name = self::escapeMarkdownV2($user->name);
        $text = "📋 *Daily Summary for {$name}*\n\n";

        // Active timer
        if ($activeTimer && $activeTimer->task) {
            $timerTitle = self::escapeMarkdownV2($activeTimer->task->title);
            $elapsed = gmdate('H:i:s', $activeTimer->started_at->diffInSeconds(now(), true));
            $elapsed = self::escapeMarkdownV2($elapsed);
            $text .= "⏱ *Active Timer:* {$timerTitle} \({$elapsed}\)\n\n";
        }

        // Stats
        $totalActive = $tasks->count();
        $text .= "📊 *Stats:*\n";
        $text .= "• Active tasks: {$totalActive}\n";
        if ($overdueTasks > 0) {
            $text .= "• ⚠️ Overdue: {$overdueTasks}\n";
        }
        if ($todayDeadlines > 0) {
            $text .= "• 📅 Due today: {$todayDeadlines}\n";
        }
        $text .= "\n";

        // Task list
        if ($tasks->isEmpty()) {
            $text .= "✅ No active tasks\. Great work\!";
        } else {
            $text .= "📝 *Your Tasks:*\n";
            foreach ($tasks->take(8) as $task) {
                $statusEmoji = match ($task->status) {
                    'in_progress' => '🔵',
                    'review' => '🟡',
                    'todo' => '⚪',
                    default => '⚫',
                };
                $priorityEmoji = match ($task->priority) {
                    'critical' => '🔴',
                    'high' => '🟠',
                    'medium' => '🟢',
                    default => '⚪',
                };
                $title = self::escapeMarkdownV2($task->title);
                $status = self::escapeMarkdownV2(str_replace('_', ' ', $task->status));
                $text .= "{$statusEmoji} {$priorityEmoji} {$title} \_{$status}\_\n";
            }
            if ($tasks->count() > 8) {
                $remaining = $tasks->count() - 8;
                $text .= "_\.\.\. and {$remaining} more_\n";
            }
        }

        $this->sendMessage($chatId, $text);
    }
}
