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

            $minDateStr = env('GMAIL_SYNC_MIN_DATE');
            if (! empty($minDateStr) && ! app()->environment('testing')) {
                $sentAt = ! empty($this->payload['date']) ? Carbon::parse($this->payload['date']) : now();
                if ($sentAt->lt(Carbon::parse($minDateStr))) {
                    Log::info('ProcessGmailAlertJob: Skipped email sent before minimum cutoff date', [
                        'sentAt' => $sentAt->toIso8601String(),
                        'minDate' => $minDateStr,
                    ]);

                    return;
                }
            }

            // Match Company (Project) & Website based on recipient or sender email domain
            $matched = $this->resolveProjectAndWebsite($recipientEmail, $customerEmail);

            $ticket = SupportTicket::where('gmail_thread_id', $threadId)->first();

            // Create new ticket if does not exist (only if trigger keywords matched)
            if (! $ticket) {
                if (empty($categories)) {
                    return;
                }

                $ticket = SupportTicket::create([
                    'gmail_thread_id' => $threadId,
                    'customer_email' => $customerEmail,
                    'recipient_email' => $recipientEmail,
                    'subject' => $subject,
                    'status' => 'open',
                    'categories' => $categories,
                    'project_id' => $matched['project_id'],
                    'website_id' => $matched['website_id'],
                ]);
            }

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

            // 3. Save Attachments with deduplication
            $savedFiles = [];
            $attachments = $this->payload['attachments'] ?? [];
            foreach ($attachments as $att) {
                $origName = $att['filename'] ?? 'file';
                $attSize = (int) ($att['size'] ?? 0);

                // Skip duplicate attachment if already present for ticket
                $alreadyExists = SupportTicketAttachment::where('support_ticket_id', $ticket->id)
                    ->where('original_filename', $origName)
                    ->exists();

                if ($alreadyExists) {
                    Log::info('Skipped duplicate attachment in alert job: '.$origName, ['ticketId' => $ticket->id]);

                    continue;
                }

                if (! empty($att['base64'])) {
                    $fileContent = base64_decode($att['base64']);
                    $origName = $att['filename'] ?? 'file';
                    $ext = pathinfo($origName, PATHINFO_EXTENSION) ?: 'bin';
                    $cleanName = Str::slug(pathinfo($origName, PATHINFO_FILENAME));
                    $filename = "{$cleanName}_".uniqid().".{$ext}";

                    $path = "tickets/{$ticket->id}/{$filename}";
                    $disk = config('filesystems.disks.private') ? 'private' : 'local';
                    Storage::disk($disk)->put($path, $fileContent);

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
            // Build task title & rich HTML description
            $taskTitle = "📩 [Ticket #{$ticket->id}] {$ticket->subject}";
            if ($cats) {
                $taskTitle = "📩 [{$cats}] {$ticket->subject}";
            }

            $descriptionHtml = $this->buildHtmlDescription($ticket, $message, $savedFiles);

            $task = Task::create([
                'project_id' => $ticket->project_id,
                'title' => Str::limit($taskTitle, 190),
                'description' => $descriptionHtml,
                'status' => 'email_inbox',
                'priority' => $priority,
            ]);

            $ticket->update(['task_id' => $task->id]);
        } else {
            // Task already exists, add formatted comment detailing update
            $task = Task::find($ticket->task_id);
            if ($task) {
                $commentText = $this->buildCommentText($ticket, $message, $savedFiles);

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

        // Sync attachments to Spatie MediaLibrary 'attachments' collection on Task safely without duplicates
        if ($task && ! empty($savedFiles)) {
            $disk = config('filesystems.disks.private') ? 'private' : 'local';
            foreach ($savedFiles as $f) {
                try {
                    if (Storage::disk($disk)->exists($f->storage_path)) {
                        $content = Storage::disk($disk)->get($f->storage_path);
                        if ($content) {
                            $task->attachDocumentStrict($content, $f->original_filename);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed adding attachment to task media library: '.$e->getMessage());
                }
            }
        }
    }

    /**
     * Build rich executive HTML card for Task description.
     */
    private function buildHtmlDescription(SupportTicket $ticket, SupportTicketMessage $message, array $savedFiles): string
    {
        $categoryList = $ticket->categories ?? ['general'];
        $primaryCategory = strtolower($categoryList[0] ?? 'alert');

        $bannerGradient = match ($primaryCategory) {
            'chargeback' => 'from-rose-600 to-red-700',
            'complaint' => 'from-amber-500 to-orange-600',
            'refund' => 'from-emerald-500 to-teal-600',
            default => 'from-sky-500 to-indigo-600',
        };

        $badgeColor = match ($primaryCategory) {
            'chargeback' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800',
            'complaint' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            'refund' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            default => 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 border-sky-200 dark:border-sky-800',
        };

        $formattedBody = $this->formatEmailBodyHtml($message->body_text ?? '');
        $dateFormatted = $message->sent_at ? $message->sent_at->format('d M Y, H:i') : now()->format('d M Y, H:i');

        $websiteName = $ticket->website?->name ?: $ticket->website?->url;
        $projectName = $ticket->project?->name;

        $html = '<div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm space-y-0">';

        // Header Hero Banner
        $html .= '<div class="px-5 py-3.5 bg-gradient-to-r '.$bannerGradient.' text-white flex items-center justify-between flex-wrap gap-2">';
        $html .= '<div class="flex items-center space-x-2">';
        $html .= '<span class="px-2.5 py-1 text-[11px] font-black uppercase tracking-wider bg-white/20 backdrop-blur-md rounded-lg flex items-center gap-1.5"><i class="fa-solid fa-bell text-[10px]"></i> '.e(strtoupper($primaryCategory)).' TICKET</span>';
        $html .= '<span class="text-xs font-semibold opacity-90">#'.e($ticket->id).'</span>';
        $html .= '</div>';
        $html .= '<span class="text-xs font-medium opacity-80 flex items-center gap-1"><i class="fa-regular fa-clock text-[10px]"></i> '.e($dateFormatted).'</span>';
        $html .= '</div>';

        // Meta Bar
        $html .= '<div class="p-5 space-y-4">';
        $html .= '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-xs bg-slate-50 dark:bg-slate-950 p-3.5 rounded-xl border border-slate-100 dark:border-slate-800/60">';
        $html .= '<div><span class="text-slate-400 font-semibold uppercase text-[10px] block mb-0.5">From Client</span><div class="font-bold text-slate-800 dark:text-slate-100 truncate" title="'.e($message->from).'">'.e($message->from).'</div></div>';
        $html .= '<div><span class="text-slate-400 font-semibold uppercase text-[10px] block mb-0.5">To Support</span><div class="font-bold text-slate-800 dark:text-slate-100 truncate" title="'.e($message->to).'">'.e($message->to).'</div></div>';

        if ($websiteName || $projectName) {
            $html .= '<div><span class="text-slate-400 font-semibold uppercase text-[10px] block mb-0.5">Matched Entity</span><div class="font-bold text-indigo-600 dark:text-indigo-400 truncate flex items-center gap-1"><i class="fa-solid fa-globe text-[10px]"></i> '.e($websiteName ?: $projectName).'</div></div>';
        } else {
            $html .= '<div><span class="text-slate-400 font-semibold uppercase text-[10px] block mb-0.5">Categories</span><div class="flex items-center gap-1 flex-wrap">';
            foreach ($categoryList as $cat) {
                $html .= '<span class="px-2 py-0.5 text-[10px] font-bold rounded border '.$badgeColor.'">'.e(strtoupper($cat)).'</span>';
            }
            $html .= '</div></div>';
        }
        $html .= '</div>';

        // Message Content Box
        $html .= '<div>';
        $html .= '<div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5 flex items-center gap-1"><i class="fa-regular fa-envelope text-[11px]"></i> Email Message Body</div>';
        $html .= '<div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 text-xs leading-relaxed text-slate-800 dark:text-slate-200 font-sans space-y-2 overflow-x-auto">';
        $html .= $formattedBody;
        $html .= '</div></div>';

        // Attachments List if any
        if (! empty($savedFiles)) {
            $html .= '<div>';
            $html .= '<div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5 flex items-center gap-1"><i class="fa-solid fa-paperclip text-[11px]"></i> Attachments ('.count($savedFiles).')</div>';
            $html .= '<div class="grid grid-cols-1 sm:grid-cols-2 gap-2">';
            foreach ($savedFiles as $f) {
                $sizeKb = round($f->size_bytes / 1024, 1);
                $html .= '<div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800 flex items-center space-x-2.5">';
                $html .= '<span class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400"><i class="fa-solid fa-file-lines text-xs"></i></span>';
                $html .= '<div class="min-w-0 flex-1"><div class="font-semibold text-xs text-slate-800 dark:text-slate-200 truncate">'.e($f->original_filename).'</div><div class="text-[10px] text-slate-400">'.$sizeKb.' KB</div></div>';
                $html .= '</div>';
            }
            $html .= '</div></div>';
        }

        $html .= '</div></div>';

        return $html;
    }

    /**
     * Build rich executive HTML for Comment reply updates.
     */
    private function buildCommentText(SupportTicket $ticket, SupportTicketMessage $message, array $savedFiles): string
    {
        $badge = '📩 **Email Reply**';
        $sender = $message->from;
        $cleanBody = $this->cleanReplyText($message->body_text ?? '');

        $text = "{$badge} from `{$sender}`\n\n{$cleanBody}";

        if (! empty($savedFiles)) {
            $text .= "\n\n📎 **New Attachments:**\n";
            foreach ($savedFiles as $f) {
                $sizeKb = round($f->size_bytes / 1024, 1);
                $text .= "• {$f->original_filename} ({$sizeKb} KB)\n";
            }
        }

        return $text;
    }

    private function cleanReplyText(string $body): string
    {
        if (empty(trim($body))) {
            return '_No text content_';
        }

        $lines = explode("\n", str_replace("\r\n", "\n", $body));
        $cleanLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/^(On\s+.*wrote:|From:.*Sent:.*To:.*|>-+Original Message-+)/i', $trimmed)) {
                break;
            }
            if (str_starts_with($trimmed, '>')) {
                continue;
            }
            $lineClean = preg_replace('/\[image:\s*[^\]]+\]/i', '', $line);
            $cleanLines[] = $lineClean;
        }

        $result = trim(implode("\n", $cleanLines));

        return $result ?: '_No new text_';
    }

    /**
     * Format email text into clean HTML, highlight keywords, and collapse quote history.
     */
    private function formatEmailBodyHtml(string $body): string
    {
        if (empty(trim($body))) {
            return '<em class="text-slate-400">No text content in message</em>';
        }

        // Split into main message vs quotes
        $lines = explode("\n", str_replace("\r\n", "\n", $body));
        $mainLines = [];
        $quoteLines = [];
        $isQuoting = false;

        foreach ($lines as $line) {
            if (preg_match('/^(On\s+.*wrote:|From:.*Sent:.*To:.*|>-+Original Message-+)/i', trim($line))) {
                $isQuoting = true;
            }

            if ($isQuoting || str_starts_with(trim($line), '>')) {
                $quoteLines[] = ltrim($line, '> ');
            } else {
                $mainLines[] = $line;
            }
        }

        $mainText = trim(implode("\n", $mainLines));
        // Strip inline image placeholders like [image: image.png]
        $mainText = preg_replace('/\[image:\s*[^\]]+\]/i', '', $mainText);
        $mainText = trim($mainText);
        $quoteText = trim(implode("\n", $quoteLines));

        $html = nl2br(e($mainText));

        // Highlight trigger words
        $keywords = ['refund', 'chargeback', 'dispute', 'complaint', 'fraud', 'scam', 'unauthorized', 'money back'];
        foreach ($keywords as $kw) {
            $html = preg_replace_callback('/\b('.preg_quote($kw, '/').')\b/i', function ($m) {
                return '<mark class="bg-amber-200 dark:bg-amber-900/80 text-amber-900 dark:text-amber-200 px-1 py-0.5 rounded font-bold">'.$m[0].'</mark>';
            }, $html);
        }

        if (! empty($quoteText)) {
            $quoteHtml = nl2br(e($quoteText));
            $html .= '<details class="mt-3"><summary class="text-[11px] font-semibold text-slate-400 hover:text-indigo-500 cursor-pointer select-none">Show quoted message history</summary><blockquote class="mt-2 pl-3 border-l-2 border-slate-300 dark:border-slate-700 text-slate-400 text-xs italic space-y-1">'.$quoteHtml.'</blockquote></details>';
        }

        return $html;
    }
}
