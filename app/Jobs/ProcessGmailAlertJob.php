<?php

namespace App\Jobs;

use App\Models\Comment;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\Task;
use App\Models\Website;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessGmailAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload) {}

    public function handle(): void
    {
        $threadId = $this->payload['thread_id'] ?? null;
        $messageId = $this->payload['message_id'] ?? null;

        if (! $threadId || ! $messageId) {
            Log::warning('ProcessGmailAlertJob missing thread_id or message_id', $this->payload);

            return;
        }

        // Deduplication: if message already processed, exit
        if (SupportTicketMessage::where('gmail_message_id', $messageId)->exists()) {
            return;
        }

        DB::transaction(function () use ($threadId, $messageId) {
            $fromRaw = $this->payload['from'] ?? '';
            $toRaw = $this->payload['to'] ?? '';

            $customerEmail = $this->extractEmail($fromRaw);
            $recipientEmail = $this->extractEmail($toRaw);
            $subject = $this->payload['subject'] ?? 'No Subject';
            $bodyText = $this->payload['body_text'] ?? '';
            $categories = $this->payload['categories'] ?? [];

            // Match Company (Project) & Website based on recipient or sender email domain
            $matched = $this->resolveProjectAndWebsite($recipientEmail, $customerEmail);

            // 1. Find or create SupportTicket
            $ticket = SupportTicket::firstOrCreate(
                ['gmail_thread_id' => $threadId],
                [
                    'customer_email' => $customerEmail,
                    'recipient_email' => $recipientEmail,
                    'subject' => $subject,
                    'status' => 'open',
                    'categories' => $categories,
                    'project_id' => $matched['project_id'],
                    'website_id' => $matched['website_id'],
                ]
            );

            $isExistingTicket = ! $ticket->wasRecentlyCreated;

            // Merge categories if updated
            if ($isExistingTicket && ! empty($categories)) {
                $mergedCats = array_values(array_unique(array_merge($ticket->categories ?? [], $categories)));
                $ticket->update(['categories' => $mergedCats]);
            }

            // Update project/website if missing and now resolved
            if (! $ticket->project_id && $matched['project_id']) {
                $ticket->update([
                    'project_id' => $matched['project_id'],
                    'website_id' => $matched['website_id'],
                ]);
            }

            // 2. Record Message
            $sentAt = ! empty($this->payload['date'])
                ? Carbon::parse($this->payload['date'])
                : now();

            $message = SupportTicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'gmail_message_id' => $messageId,
                'from' => $fromRaw,
                'to' => $toRaw,
                'body_text' => $bodyText,
                'sent_at' => $sentAt,
            ]);

            // 3. Save Attachments
            $savedFiles = [];
            $attachments = $this->payload['attachments'] ?? [];
            foreach ($attachments as $att) {
                if (! empty($att['base64'])) {
                    $fileContent = base64_decode($att['base64']);
                    $origName = $att['filename'] ?? 'file';
                    $ext = pathinfo($origName, PATHINFO_EXTENSION) ?: 'bin';
                    $cleanName = Str::slug(pathinfo($origName, PATHINFO_FILENAME));
                    $filename = "{$cleanName}_".uniqid().".{$ext}";

                    $path = "tickets/{$ticket->id}/{$filename}";
                    Storage::disk('private')->put($path, $fileContent);

                    $attachmentModel = SupportTicketAttachment::create([
                        'support_ticket_id' => $ticket->id,
                        'support_ticket_message_id' => $message->id,
                        'original_filename' => $origName,
                        'storage_path' => $path,
                        'mime_type' => $att['mime_type'] ?? 'application/octet-stream',
                        'size_bytes' => $att['size'] ?? strlen($fileContent),
                    ]);

                    $savedFiles[] = $attachmentModel;
                }
            }

            // 4. Create or update associated CRM Task
            $this->syncCrmTask($ticket, $message, $savedFiles, $isExistingTicket);
        });
    }

    /**
     * Extract raw email address from header string like "John Doe <john@example.com>".
     */
    private function extractEmail(string $raw): string
    {
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $raw, $matches)) {
            return strtolower($matches[0]);
        }

        return strtolower(trim($raw));
    }

    /**
     * Resolve Project and Website from email domains.
     *
     * @return array{project_id: ?int, website_id: ?int}
     */
    private function resolveProjectAndWebsite(string $recipientEmail, string $customerEmail): array
    {
        $emailsToTest = array_filter([$recipientEmail, $customerEmail]);

        foreach ($emailsToTest as $email) {
            $parts = explode('@', $email);
            if (count($parts) < 2) {
                continue;
            }
            $domain = strtolower($parts[1]);

            // Skip generic free mail providers for company matching
            if (in_array($domain, ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com'], true)) {
                continue;
            }

            $website = Website::where(function ($q) use ($domain) {
                $q->where('url', 'like', "%{$domain}%")
                    ->orWhere('name', 'like', "%{$domain}%");
            })->first();

            if ($website) {
                return [
                    'project_id' => $website->project_id,
                    'website_id' => $website->id,
                ];
            }

            // Try matching Project company name
            $project = Project::where('name', 'like', "%{$domain}%")->first();
            if ($project) {
                return [
                    'project_id' => $project->id,
                    'website_id' => null,
                ];
            }
        }

        return ['project_id' => null, 'website_id' => null];
    }

    /**
     * Create or update CRM Task for ticket.
     *
     * @param  array<SupportTicketAttachment>  $savedFiles
     */
    private function syncCrmTask(SupportTicket $ticket, SupportTicketMessage $message, array $savedFiles, bool $isExistingTicket): void
    {
        $cats = implode(', ', array_map('ucfirst', $ticket->categories ?? ['General Alert']));
        $priority = 'medium';
        foreach ($ticket->categories ?? [] as $cat) {
            if (in_array(strtolower($cat), ['chargeback', 'complaint'], true)) {
                $priority = 'high';
                break;
            }
        }

        if (! $ticket->task_id) {
            // Build task title & description
            $taskTitle = "📩 [Ticket #{$ticket->id}] {$ticket->subject}";
            if ($cats) {
                $taskTitle = "📩 [{$cats}] {$ticket->subject}";
            }

            $description = "📌 **Gmail Ticket #{$ticket->id}**\n\n";
            $description .= "👤 **From:** {$message->from}\n";
            $description .= "📫 **To:** {$message->to}\n";
            $description .= "🏷️ **Categories:** {$cats}\n\n";
            $description .= "💬 **Message:**\n".($message->body_text ?: '_No text content_')."\n";

            if (! empty($savedFiles)) {
                $description .= "\n📎 **Attachments (".count($savedFiles)."):**\n";
                foreach ($savedFiles as $f) {
                    $description .= "• {$f->original_filename} (".round($f->size_bytes / 1024, 1)." KB)\n";
                }
            }

            $task = Task::create([
                'project_id' => $ticket->project_id,
                'title' => Str::limit($taskTitle, 190),
                'description' => $description,
                'status' => 'todo',
                'priority' => $priority,
            ]);

            $ticket->update(['task_id' => $task->id]);
        } else {
            // Task already exists, add comment detailing update
            $task = Task::find($ticket->task_id);
            if ($task) {
                $commentText = "📩 **New email reply received in Thread #{$ticket->gmail_thread_id}**\n\n";
                $commentText .= "👤 **From:** {$message->from}\n";
                $commentText .= "💬 **Body:**\n".($message->body_text ?: '_No text content_');

                if (! empty($savedFiles)) {
                    $commentText .= "\n\n📎 **New Attachments:**\n";
                    foreach ($savedFiles as $f) {
                        $commentText .= "• {$f->original_filename} (".round($f->size_bytes / 1024, 1)." KB)\n";
                    }
                }

                Comment::create([
                    'task_id' => $task->id,
                    'user_id' => null, // System / Webhook
                    'content' => $commentText,
                ]);

                // Bump priority if new categories contain chargeback/complaint
                if ($priority === 'high' && $task->priority !== 'high') {
                    $task->update(['priority' => 'high']);
                }
            }
        }
    }
}
