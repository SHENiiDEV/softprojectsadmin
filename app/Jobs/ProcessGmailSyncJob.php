<?php

namespace App\Jobs;

use App\Models\Comment;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\Task;
use App\Models\Website;
use App\Services\GmailSyncService;
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

class ProcessGmailSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const KEYWORDS = [
        'refund' => [
            'refund', 'i want refund', 'money back', 'reimbursement',
            'возврат средств', 'верните деньги', 'сделайте возврат',
        ],
        'chargeback' => [
            'chargeback', 'charge back', 'dispute', 'чарджбек',
            'оспаривание платежа', 'оспаривание',
        ],
        'fraud' => [
            'fraud', 'scam', 'unauthorized transaction', 'stolen card',
            'мошенничество', 'несанкционированное списание',
        ],
    ];

    public function __construct(public string $historyId, public ?string $emailAddress = null) {}

    public function handle(GmailSyncService $syncService): void
    {
        $addedMessages = $syncService->fetchAddedMessages($this->historyId);

        if (empty($addedMessages)) {
            return;
        }

        foreach ($addedMessages as $item) {
            $msgId = $item['id'];

            // Skip if message already recorded in database
            if (SupportTicketMessage::where('gmail_message_id', $msgId)->exists()) {
                continue;
            }

            $rawMsg = $syncService->getMessage($msgId);
            if (! $rawMsg) {
                continue;
            }

            $parsed = $syncService->parseMessagePayload($rawMsg);
            $this->processParsedMessage($syncService, $parsed);
        }
    }

    /**
     * Process a single parsed Gmail message.
     *
     * @param  array{
     *     id: string,
     *     threadId: string,
     *     from: string,
     *     to: string,
     *     subject: string,
     *     date: ?string,
     *     body: string,
     *     attachments: array<array{filename: string, mimeType: string, attachmentId: string, size: int}>,
     *     labelIds: array<string>
     * }  $msg
     */
    protected function processParsedMessage(GmailSyncService $syncService, array $msg): void
    {
        if (SupportTicketMessage::where('gmail_message_id', $msg['id'])->exists()) {
            return;
        }

        DB::transaction(function () use ($syncService, $msg) {
            $threadId = $msg['threadId'];
            $fromRaw = $msg['from'];
            $toRaw = $msg['to'];
            $customerEmail = $this->extractEmail($fromRaw);
            $recipientEmail = $this->extractEmail($toRaw);
            $subject = $msg['subject'] ?: 'No Subject';
            $bodyText = $msg['body'];

            $minDateStr = env('GMAIL_SYNC_MIN_DATE');
            if (! empty($minDateStr) && ! app()->environment('testing')) {
                $sentAt = ! empty($msg['date']) ? Carbon::parse($msg['date']) : now();
                if ($sentAt->lt(Carbon::parse($minDateStr))) {
                    Log::info('GmailSyncJob: Skipped email sent before minimum cutoff date', [
                        'sentAt' => $sentAt->toIso8601String(),
                        'minDate' => $minDateStr,
                    ]);

                    return;
                }
            }

            $isOutgoing = in_array('SENT', $msg['labelIds'], true);
            $matchedCategories = $this->classifyKeywords($subject, $bodyText);

            $ticket = SupportTicket::where('gmail_thread_id', $threadId)->first();

            // Scenario 1: New thread email from client
            if (! $ticket && ! $isOutgoing) {
                if (empty($matchedCategories)) {
                    return;
                }

                $matchedEntity = $this->resolveProjectAndWebsite($recipientEmail, $customerEmail);

                $ticket = SupportTicket::create([
                    'gmail_thread_id' => $threadId,
                    'customer_email' => $customerEmail,
                    'recipient_email' => $recipientEmail,
                    'subject' => $subject,
                    'status' => 'open',
                    'categories' => $matchedCategories,
                    'project_id' => $matchedEntity['project_id'],
                    'website_id' => $matchedEntity['website_id'],
                ]);
            } elseif (! $ticket && $isOutgoing) {
                // Outgoing email for non-tracked thread -> skip
                return;
            } else {
                // Scenario 2 & 3: Existing ticket thread update
                if ($isOutgoing) {
                    $ticket->update(['status' => 'answered']);
                } else {
                    $ticket->update(['status' => 'customer_replied']);
                    if (! empty($matchedCategories)) {
                        $merged = array_values(array_unique(array_merge($ticket->categories ?? [], $matchedCategories)));
                        $ticket->update(['categories' => $merged]);
                    }
                }
            }

            // Save Message Record
            $sentAt = ! empty($msg['date']) ? Carbon::parse($msg['date']) : now();
            $ticketMessage = SupportTicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'gmail_message_id' => $msg['id'],
                'from' => $fromRaw,
                'to' => $toRaw,
                'is_outgoing' => $isOutgoing,
                'body_text' => $bodyText,
                'sent_at' => $sentAt,
            ]);

            // Save Attachments with deduplication
            $savedFiles = [];
            $disk = config('filesystems.disks.private') ? 'private' : 'local';

            foreach ($msg['attachments'] as $att) {
                $origName = $att['filename'] ?: 'file';
                $attSize = (int) ($att['size'] ?? 0);

                // Skip duplicate attachment download if already present for ticket
                $alreadyExists = SupportTicketAttachment::where('support_ticket_id', $ticket->id)
                    ->where('original_filename', $origName)
                    ->exists();

                if ($alreadyExists) {
                    Log::info('Skipped duplicate attachment for ticket: '.$origName, ['ticketId' => $ticket->id]);

                    continue;
                }

                $fileContent = $syncService->downloadAttachment($msg['id'], $att['attachmentId']);
                if ($fileContent) {
                    $ext = pathinfo($origName, PATHINFO_EXTENSION) ?: 'bin';
                    $cleanName = Str::slug(pathinfo($origName, PATHINFO_FILENAME));
                    $filename = "{$cleanName}_".uniqid().".{$ext}";
                    $path = "tickets/{$ticket->id}/{$filename}";

                    Storage::disk($disk)->put($path, $fileContent);

                    $attachmentModel = SupportTicketAttachment::create([
                        'support_ticket_id' => $ticket->id,
                        'support_ticket_message_id' => $ticketMessage->id,
                        'original_filename' => $origName,
                        'storage_path' => $path,
                        'mime_type' => $att['mimeType'] ?: 'application/octet-stream',
                        'size_bytes' => $attSize ?: strlen($fileContent),
                    ]);

                    $savedFiles[] = $attachmentModel;
                }
            }

            // Sync with CRM Task
            $this->syncCrmTask($ticket, $ticketMessage, $savedFiles, $isOutgoing);
        });
    }

    /**
     * Classify message subject and body text against KEYWORDS dictionary.
     *
     * @return array<string>
     */
    protected function classifyKeywords(string $subject, string $body): array
    {
        $text = strtolower($subject."\n".$body);
        $matched = [];

        foreach (self::KEYWORDS as $category => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($text, strtolower($kw))) {
                    $matched[] = $category;
                    break;
                }
            }
        }

        return array_values(array_unique($matched));
    }

    /**
     * Extract raw email address from header string like "John Doe <john@example.com>".
     */
    protected function extractEmail(string $raw): string
    {
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $raw, $matches)) {
            return strtolower($matches[0]);
        }

        return strtolower(trim($raw));
    }

    /**
     * Resolve Project and Website from recipient or customer domain.
     *
     * @return array{project_id: ?int, website_id: ?int}
     */
    protected function resolveProjectAndWebsite(string $recipientEmail, string $customerEmail): array
    {
        $emailsToTest = array_filter([$recipientEmail, $customerEmail]);

        foreach ($emailsToTest as $email) {
            $parts = explode('@', $email);
            if (count($parts) < 2) {
                continue;
            }
            $domain = strtolower($parts[1]);

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
    protected function syncCrmTask(SupportTicket $ticket, SupportTicketMessage $message, array $savedFiles, bool $isOutgoing): void
    {
        $cats = implode(', ', array_map('ucfirst', $ticket->categories ?? ['General Alert']));
        $priority = 'medium';
        foreach ($ticket->categories ?? [] as $cat) {
            if (in_array(strtolower($cat), ['chargeback', 'complaint', 'fraud'], true)) {
                $priority = 'high';
                break;
            }
        }

        if (! $ticket->task_id) {
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
            $task = Task::find($ticket->task_id);
            if ($task) {
                $commentText = $this->buildCommentText($ticket, $message, $savedFiles, $isOutgoing);

                Comment::create([
                    'task_id' => $task->id,
                    'user_id' => null, // System / Webhook
                    'content' => $commentText,
                ]);

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

    protected function buildHtmlDescription(SupportTicket $ticket, SupportTicketMessage $message, array $savedFiles): string
    {
        $categoryList = $ticket->categories ?? ['general'];
        $primaryCategory = strtolower($categoryList[0] ?? 'alert');

        $bannerGradient = match ($primaryCategory) {
            'chargeback', 'fraud' => 'from-rose-600 to-red-700',
            'complaint' => 'from-amber-500 to-orange-600',
            'refund' => 'from-emerald-500 to-teal-600',
            default => 'from-sky-500 to-indigo-600',
        };

        $badgeColor = match ($primaryCategory) {
            'chargeback', 'fraud' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800',
            'complaint' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            'refund' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            default => 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 border-sky-200 dark:border-sky-800',
        };

        $formattedBody = $this->formatEmailBodyHtml($message->body_text ?? '');
        $dateFormatted = $message->sent_at ? $message->sent_at->format('d M Y, H:i') : now()->format('d M Y, H:i');

        $websiteName = $ticket->website?->name ?: $ticket->website?->url;
        $projectName = $ticket->project?->name;

        $html = '<div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm space-y-0">';

        $html .= '<div class="px-5 py-3.5 bg-gradient-to-r '.$bannerGradient.' text-white flex items-center justify-between flex-wrap gap-2">';
        $html .= '<div class="flex items-center space-x-2">';
        $html .= '<span class="px-2.5 py-1 text-[11px] font-black uppercase tracking-wider bg-white/20 backdrop-blur-md rounded-lg flex items-center gap-1.5"><i class="fa-solid fa-bell text-[10px]"></i> '.e(strtoupper($primaryCategory)).' TICKET</span>';
        $html .= '<span class="text-xs font-semibold opacity-90">#'.e($ticket->id).'</span>';
        $html .= '</div>';
        $html .= '<span class="text-xs font-medium opacity-80 flex items-center gap-1"><i class="fa-regular fa-clock text-[10px]"></i> '.e($dateFormatted).'</span>';
        $html .= '</div>';

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

        $html .= '<div>';
        $html .= '<div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5 flex items-center gap-1"><i class="fa-regular fa-envelope text-[11px]"></i> Email Message Body</div>';
        $html .= '<div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 text-xs leading-relaxed text-slate-800 dark:text-slate-200 font-sans space-y-2 overflow-x-auto">';
        $html .= $formattedBody;
        $html .= '</div></div>';

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

    protected function buildCommentText(SupportTicket $ticket, SupportTicketMessage $message, array $savedFiles, bool $isOutgoing): string
    {
        $badge = $isOutgoing ? '🟢 **Support Reply**' : '📩 **Email Reply**';
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

    protected function cleanReplyText(string $body): string
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

    protected function formatEmailBodyHtml(string $body): string
    {
        if (empty(trim($body))) {
            return '<em class="text-slate-400">No text content in message</em>';
        }

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

        $keywords = ['refund', 'chargeback', 'dispute', 'complaint', 'fraud', 'scam', 'unauthorized', 'money back', 'возврат', 'чарджбек', 'мошенничество'];
        foreach ($keywords as $kw) {
            $html = preg_replace_callback('/\b('.preg_quote($kw, '/').')\b/iu', function ($m) {
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
