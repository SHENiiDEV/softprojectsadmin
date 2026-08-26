<?php

namespace App\Console\Commands;

use App\Jobs\ProcessGmailSyncJob;
use App\Models\SupportTicket;
use App\Services\GmailSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

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

            // Backfill attachments to task Spatie MediaLibrary 'documents' collection for existing tickets
            $tickets = SupportTicket::whereNotNull('task_id')->with('attachments', 'task')->get();
            $disk = config('filesystems.disks.private') ? 'private' : 'local';
            foreach ($tickets as $ticket) {
                if (! $ticket->task) {
                    continue;
                }
                foreach ($ticket->attachments as $att) {
                    $existsInMedia = $ticket->task->getMedia('attachments')->contains('file_name', $att->original_filename);
                    if (! $existsInMedia && Storage::disk($disk)->exists($att->storage_path)) {
                        $content = Storage::disk($disk)->get($att->storage_path);
                        if ($content) {
                            $ticket->task->addMediaFromString($content)
                                ->usingFileName($att->original_filename)
                                ->toMediaCollection('attachments');
                        }
                    }
                }
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error("❌ Error syncing recent messages: {$e->getMessage()}");

            return 1;
        }
    }
}
