<?php

namespace App\Console\Commands;

use App\Jobs\SendTelegramMessageJob;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class SendDailyDigest extends Command
{
    protected $signature = 'digest:daily';

    protected $description = 'Send daily task digest to all users via Telegram on weekdays at 9:00 AM';

    public function handle(TelegramService $telegramService): int
    {
        if (now()->isWeekend()) {
            $this->info('Skipping daily digest: today is a weekend.');

            return 0;
        }

        $this->info('Sending daily digest...');

        $users = User::whereNotNull('telegram_id')
            ->get();

        $buttons = [
            'inline_keyboard' => [
                [
                    ['text' => '📋 Открыть My Work / Задачи', 'url' => route('tasks.kanban')],
                ],
            ],
        ];

        foreach ($users as $user) {
            $text = $telegramService->buildSummaryText($user, "Daily Summary for {$user->name}");

            SendTelegramMessageJob::dispatch($user->telegram_id, $text, $buttons);
            $this->info("Digest sent to {$user->name}");
        }

        $this->info('Daily digest complete.');

        return 0;
    }
}
