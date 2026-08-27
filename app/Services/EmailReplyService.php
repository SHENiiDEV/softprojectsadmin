<?php

namespace App\Services;

use App\Models\Credential;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class EmailReplyService
{
    public function __construct(
        protected GmailSyncService $gmailSyncService
    ) {}

    /**
     * Resolve sending credentials for a support ticket or target email.
     */
    public function resolveCredential(?Project $project, string $targetEmail): ?Credential
    {
        if (! $project) {
            return null;
        }

        $cleanTarget = strtolower(trim($targetEmail));

        // 1. Search by exact login matching target email
        $cred = Credential::where('project_id', $project->id)
            ->whereRaw('LOWER(login) = ?', [$cleanTarget])
            ->first();

        if ($cred) {
            return $cred;
        }

        // 2. Search by credential type 'email', 'smtp', 'mail', 'hosting'
        $cred = Credential::where('project_id', $project->id)
            ->whereIn('type', ['email', 'smtp', 'mail', 'hosting', 'domain'])
            ->where(function ($q) use ($cleanTarget) {
                $q->where('login', 'like', "%{$cleanTarget}%")
                    ->orWhere('name', 'like', "%{$cleanTarget}%")
                    ->orWhere('provider_url', 'like', "%{$cleanTarget}%");
            })
            ->first();

        if ($cred) {
            return $cred;
        }

        // 3. Fallback: any email/smtp credential for the project
        return Credential::where('project_id', $project->id)
            ->whereIn('type', ['email', 'smtp', 'mail'])
            ->first();
    }

    /**
     * Detect SMTP host and port presets for Namecheap, Hostinger, Gmail, etc.
     */
    public function detectSmtpSettings(Credential $cred, string $fromEmail): array
    {
        $fields = is_array($cred->fields) ? $cred->fields : [];
        $providerUrl = strtolower((string) $cred->provider_url);
        $credName = strtolower((string) $cred->name);

        $host = $fields['smtp_host'] ?? null;
        $port = (int) ($fields['smtp_port'] ?? 0);
        $encryption = $fields['smtp_encryption'] ?? 'tls';

        if (! $host) {
            if (str_contains($providerUrl, 'privateemail') || str_contains($credName, 'privateemail') || str_contains($credName, 'namecheap')) {
                $host = 'mail.privateemail.com';
                $port = $port ?: 465;
                $encryption = 'ssl';
            } elseif (str_contains($providerUrl, 'hostinger') || str_contains($credName, 'hostinger')) {
                $host = 'smtp.hostinger.com';
                $port = $port ?: 465;
                $encryption = 'ssl';
            } elseif (str_contains($providerUrl, 'gmail') || str_contains($credName, 'gmail') || str_contains($fromEmail, '@gmail.com')) {
                $host = 'smtp.gmail.com';
                $port = $port ?: 587;
                $encryption = 'tls';
            } elseif (str_contains($providerUrl, 'office365') || str_contains($providerUrl, 'outlook') || str_contains($credName, 'outlook')) {
                $host = 'smtp.office365.com';
                $port = $port ?: 587;
                $encryption = 'tls';
            } else {
                // Custom domain fallback (e.g. mail.domain.com)
                $domain = str_contains($fromEmail, '@') ? substr(strrchr($fromEmail, '@'), 1) : '';
                $host = $domain ? "mail.{$domain}" : 'mail.privateemail.com';
                $port = $port ?: 465;
                $encryption = 'ssl';
            }
        }

        if (! $port) {
            $port = 587;
        }

        return [
            'host' => $host,
            'port' => $port,
            'encryption' => strtolower($encryption),
            'username' => $cred->login ?: $fromEmail,
            'password' => $cred->password,
            'provider' => $this->getProviderLabel($host),
        ];
    }

    public function getProviderLabel(string $host): string
    {
        if (str_contains($host, 'privateemail')) {
            return 'Namecheap PrivateEmail';
        }
        if (str_contains($host, 'hostinger')) {
            return 'Hostinger Mail';
        }
        if (str_contains($host, 'gmail')) {
            return 'Google Workspace / Gmail';
        }
        if (str_contains($host, 'office365') || str_contains($host, 'outlook')) {
            return 'Microsoft Office 365';
        }

        return 'Custom SMTP ('.$host.')';
    }

    /**
     * Send email reply using custom SMTP credentials or fallback to Gmail API.
     */
    public function sendReply(
        SupportTicket $ticket,
        string $replyText,
        string $senderName
    ): array {
        $toEmail = $ticket->customer_email;
        $subject = $ticket->subject ?: 'Support Ticket Reply';
        $threadId = $ticket->gmail_thread_id;

        // Determine recipient / support email from ticket messages
        $firstMsg = $ticket->messages()->orderBy('id', 'asc')->first();
        $supportEmail = $firstMsg ? $firstMsg->to : 'info@sivora.co.uk';

        // Extract clean email from "Name <email@domain.com>"
        if (preg_match('/<([^>]+)>/', $supportEmail, $matches)) {
            $fromEmail = $matches[1];
        } else {
            $fromEmail = trim($supportEmail);
        }

        $project = $ticket->project;
        $cred = $this->resolveCredential($project, $fromEmail);

        $sentVia = 'Gmail API';
        $sentSuccess = false;

        // 1. Try Custom SMTP if credentials exist
        if ($cred && ! empty($cred->password)) {
            $settings = $this->detectSmtpSettings($cred, $fromEmail);
            try {
                $isSsl = $settings['encryption'] === 'ssl' || $settings['port'] === 465;
                $transport = new EsmtpTransport(
                    $settings['host'],
                    $settings['port'],
                    $isSsl
                );
                $transport->setUsername($settings['username']);
                $transport->setPassword($settings['password']);

                $mailer = new Mailer('custom_smtp', app('view'), $transport, app('events'));
                $mailer->raw($replyText, function ($message) use ($fromEmail, $senderName, $toEmail, $subject) {
                    $message->from($fromEmail, $senderName)
                        ->to($toEmail)
                        ->subject('Re: '.preg_replace('/^Re:\s*/i', '', $subject));
                });

                $sentVia = $settings['provider'];
                $sentSuccess = true;
            } catch (\Throwable $e) {
                Log::warning('Custom SMTP send failed, falling back to Gmail API', [
                    'from' => $fromEmail,
                    'host' => $settings['host'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 2. Fallback to Gmail API thread reply
        if (! $sentSuccess && $threadId) {
            $lastMsg = $ticket->messages()->where('is_outgoing', false)->orderBy('id', 'desc')->first();
            $inReplyToId = $lastMsg ? $lastMsg->gmail_message_id : null;

            $msgId = $this->gmailSyncService->sendReplyEmail(
                $toEmail,
                $subject,
                $replyText,
                $threadId,
                $inReplyToId,
                $fromEmail,
                $senderName
            );

            if ($msgId) {
                $sentSuccess = true;
                $sentVia = 'Gmail API (Forwarding)';
            }
        }

        if (! $sentSuccess) {
            throw new \RuntimeException("Failed sending email reply to {$toEmail}. Check credentials or Gmail connection.");
        }

        // Record outgoing message
        $outgoingMsg = SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'gmail_message_id' => 'crm_reply_'.uniqid(),
            'from' => "{$senderName} <{$fromEmail}>",
            'to' => $toEmail,
            'is_outgoing' => true,
            'body_text' => $replyText,
            'sent_at' => now(),
        ]);

        // Update ticket status
        $ticket->update([
            'status' => 'answered',
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'sent_via' => $sentVia,
            'from_email' => $fromEmail,
            'message' => $outgoingMsg,
        ];
    }
}
