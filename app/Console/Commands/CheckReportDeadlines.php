<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Models\User;
use App\Services\TelegramService;
use App\Jobs\SendTelegramMessageJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckReportDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:check-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check report deadlines and notify project managers, admins, and curators 1 day before the deadline';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        $this->info("Checking report deadlines for tomorrow: {$tomorrow}");

        // 1. Check Accounts due by tomorrow
        $reportsDueAccounts = Report::with(['project.manager'])
            ->whereDate('accounts_due_by', $tomorrow)
            ->get();

        foreach ($reportsDueAccounts as $report) {
            $this->notifyForReport($report, 'Financial Statement (Accounts)', $report->accounts_due_by);
        }

        // 2. Check Statements due by tomorrow
        $reportsDueStatements = Report::with(['project.manager'])
            ->whereDate('statements_due_by', $tomorrow)
            ->get();

        foreach ($reportsDueStatements as $report) {
            $this->notifyForReport($report, 'Confirmation Statement (Statements)', $report->statements_due_by);
        }

        $this->info('Completed checking report deadlines.');
        return 0;
    }

    /**
     * Notify manager, admins and curators for a report deadline.
     */
    protected function notifyForReport(Report $report, string $reportType, string $dueDate): void
    {
        if (!$report->project) {
            return;
        }

        $companyName = TelegramService::escapeMarkdownV2($report->project->name);
        $escapedReportType = TelegramService::escapeMarkdownV2($reportType);
        $dateStr = TelegramService::escapeMarkdownV2(Carbon::parse($dueDate)->format('d.m.Y'));

        $text = "⚠️ *Warning\\! The report deadline for {$companyName} expires tomorrow\\!*\n\n";
        $text .= "*Report Type:* {$escapedReportType}\n";
        $text .= "*Deadline:* {$dateStr}";

        $manager = $report->project->manager;
        $url = route('projects.show', ['project' => $report->project_id]);
        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => '🔗 Open Company', 'url' => $url]
                ]
            ]
        ];

        // 1. Notify Manager
        if ($manager && $manager->telegram_id) {
            SendTelegramMessageJob::dispatch($manager->telegram_id, $text, $replyMarkup);
        }

        // 2. Notify Admins and Curators
        $adminsAndCurators = User::role(['admin', 'curator'])
            ->whereNotNull('telegram_id')
            ->where('id', '!=', $manager?->id ?? 0)
            ->get();

        foreach ($adminsAndCurators as $user) {
            SendTelegramMessageJob::dispatch($user->telegram_id, $text, $replyMarkup);
        }
    }
}
