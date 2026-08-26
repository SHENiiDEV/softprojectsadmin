<?php

namespace App\Console\Commands;

use App\Jobs\SendTelegramMessageJob;
use App\Models\Task;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckTaskDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:check-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check task deadlines and notify assignees in Telegram 24 hours before the deadline';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tomorrow = Carbon::tomorrow()->toDateString();
        $this->info("Checking task deadlines for tomorrow: {$tomorrow}");

        $tasks = Task::with('assignee')
            ->whereDate('due_date', $tomorrow)
            ->where('status', '!=', 'done')
            ->whereNotNull('assigned_to')
            ->where('deadline_reminder_sent', false)
            ->get();

        foreach ($tasks as $task) {
            $assignee = $task->assignee;
            if (! $assignee || ! $assignee->telegram_id) {
                continue;
            }

            $escapedTitle = TelegramService::escapeMarkdownV2($task->title);
            $dateStr = TelegramService::escapeMarkdownV2(Carbon::parse($task->due_date)->format('d.m.Y'));

            $text = "⚠️ *Warning\\! Your task deadline expires tomorrow\\!*\n\n";
            $text .= "*Task:* {$escapedTitle}\n";
            $text .= "*Deadline:* {$dateStr}";

            $url = route('tasks.kanban', ['task_id' => $task->id]);
            $replyMarkup = [
                'inline_keyboard' => [
                    [
                        ['text' => '🔗 Open Task', 'url' => $url],
                    ],
                ],
            ];

            SendTelegramMessageJob::dispatch($assignee->telegram_id, $text, $replyMarkup);

            $task->deadline_reminder_sent = true;
            $task->save();

            $this->info("Notified {$assignee->name} about task '{$task->title}' due tomorrow.");
        }

        $this->info('Completed checking task deadlines.');

        return 0;
    }
}
