<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Task;
use App\Services\TelegramService;
use App\Jobs\SendTelegramMessageJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDailyDigest extends Command
{
    protected $signature = 'digest:daily';
    protected $description = 'Send daily task digest to all users via Telegram at 9:00 AM';

    public function handle(): int
    {
        $this->info('Sending daily digest...');

        $users = User::whereNotNull('telegram_id')
            ->get();

        foreach ($users as $user) {
            $activeTasks = Task::where('assigned_to', $user->id)
                ->whereNotIn('status', ['done'])
                ->count();

            $overdue = Task::where('assigned_to', $user->id)
                ->whereNotNull('due_date')
                ->where('due_date', '<', now()->startOfDay())
                ->whereNotIn('status', ['done'])
                ->count();

            $dueToday = Task::where('assigned_to', $user->id)
                ->whereDate('due_date', today())
                ->whereNotIn('status', ['done'])
                ->count();

            $name = TelegramService::escapeMarkdownV2($user->name);
            $today = TelegramService::escapeMarkdownV2(Carbon::now()->format('d M Y'));

            $text = "☀️ *Good morning, {$name}\!*\n";
            $text .= "_{$today}_\n\n";
            $text .= "📊 *Your work today:*\n";
            $text .= "• Active tasks: *{$activeTasks}*\n";

            if ($overdue > 0) {
                $text .= "• ⚠️ Overdue: *{$overdue}* \— needs attention\!\n";
            }
            if ($dueToday > 0) {
                $text .= "• 📅 Due today: *{$dueToday}*\n";
            }

            if ($activeTasks === 0 && $overdue === 0) {
                $text .= "\n✅ You have no active tasks\. Enjoy the day\!";
            } else {
                $text .= "\nUse /summary for a full task list\.";
            }

            SendTelegramMessageJob::dispatch($user->telegram_id, $text);
            $this->info("Digest sent to {$user->name}");
        }

        $this->info('Daily digest complete.');
        return 0;
    }
}
