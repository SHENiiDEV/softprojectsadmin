<?php

namespace App\Console\Commands;

use App\Services\GmailSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GmailSetupWatchCommand extends Command
{
    protected $signature = 'gmail:watch';

    protected $description = 'Register or renew 7-day Gmail API watch subscription with Google Cloud Pub/Sub';

    public function handle(GmailSyncService $syncService): int
    {
        $this->info('Registering Gmail watch subscription with Google Pub/Sub...');

        try {
            $result = $syncService->watch();

            $historyId = $result['historyId'];
            $expirationTimestamp = (int) ($result['expiration'] / 1000);
            $expirationDate = Carbon::createFromTimestamp($expirationTimestamp)->toDateTimeString();

            $this->info('✅ Gmail Watch registered successfully!');
            $this->line("• History ID: {$historyId}");
            $this->line("• Expires at: {$expirationDate}");

            return 0;
        } catch (\Throwable $e) {
            $this->error("❌ Failed to register Gmail Watch: {$e->getMessage()}");

            return 1;
        }
    }
}
