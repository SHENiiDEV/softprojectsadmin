<?php

namespace App\Console\Commands;

use App\Jobs\ProcessGmailSyncJob;
use App\Models\Task;
use App\Services\GmailSyncService;
use Illuminate\Console\Command;

class GmailSyncRecentCommand extends Command
{
    protected $signature = 'gmail:sync-recent 
                            {--email= : Filter by sender/recipient email (defaults to GMAIL_TEST_EMAILS or mihails.horolskis@gmail.com)} 
                            {--limit=50 : Maximum number of emails to inspect} 
                            {--query= : Custom Gmail search query}';

    protected $description = 'Sync messages from Gmail mailbox into support tickets & CRM tasks';

    public function handle(GmailSyncService $syncService): int
    {
        $limit = (int) $this->option('limit');
        $email = $this->option('email') ?: env('GMAIL_TEST_EMAILS', 'mihails.horolskis@gmail.com');
        $customQuery = $this->option('query');

        $query = '';
        if ($customQuery) {
            $query = $customQuery;
        } elseif (! empty($email)) {
            $firstEmail = trim(explode(',', $email)[0]);
            $query = "from:{$firstEmail} OR to:{$firstEmail}";
        }

        $this->info("Fetching messages from Gmail (limit: {$limit}".($query ? ", query: '{$query}'" : '').')...');

        try {
            $gmail = $syncService->getGmailService();
            $params = ['maxResults' => $limit];
            if (! empty($query)) {
                $params['q'] = $query;
            }

            $listResponse = $gmail->users_messages->listUsersMessages('me', $params);

            $messages = $listResponse->getMessages() ?? [];
            if (empty($messages)) {
                $this->info('No messages found in Gmail inbox.');

                return 0;
            }

            $count = 0;
            foreach ($messages as $msgItem) {
                $msgId = $msgItem->getId();
                $rawMsg = $syncService->getMessage($msgId);
                if (! $rawMsg) {
                    continue;
                }

                $parsed = $syncService->parseMessagePayload($rawMsg);

                $job = new ProcessGmailSyncJob('manual');
                $reflector = new \ReflectionClass(ProcessGmailSyncJob::class);
                $method = $reflector->getMethod('processParsedMessage');
                $method->setAccessible(true);
                $method->invoke($job, $syncService, $parsed);

                $count++;
            }

            $this->info("✅ Successfully inspected and processed {$count} recent Gmail messages!");

            // Run duplicate media cleanup & strict attachment on all tasks
            $tasks = Task::has('media')->get();
            foreach ($tasks as $task) {
                $task->cleanupDuplicateMedia();
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error("❌ Error syncing recent messages: {$e->getMessage()}");

            return 1;
        }
    }
}
