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
        $cleanTarget = strtolower(trim($targetEmail));

        // 1. Search by exact login matching target email (project-specific)
        if ($project) {
            $cred = Credential::where('project_id', $project->id)
                ->whereRaw('LOWER(login) = ?', [$cleanTarget])
                ->first();

            if ($cred) {
                return $cred;
            }
        }

        // 2. Global search by exact login
        $cred = Credential::whereRaw('LOWER(login) = ?', [$cleanTarget])->first();
        if ($cred) {
            return $cred;
        }

        // 3. Search by login or name containing target email (project-specific)
        if ($project) {
            $cred = Credential::where('project_id', $project->id)
                ->where(function ($q) use ($cleanTarget) {
                    $q->whereRaw('LOWER(login) LIKE ?', ["%{$cleanTarget}%"])
                        ->orWhereRaw('LOWER(name) LIKE ?', ["%{$cleanTarget}%"])
                        ->orWhereRaw('LOWER(provider_url) LIKE ?', ["%{$cleanTarget}%"]);
                })->first();

            if ($cred) {
                return $cred;
            }
        }

        // 4. Global search by login or name containing target email
        $cred = Credential::where(function ($q) use ($cleanTarget) {
            $q->whereRaw('LOWER(login) LIKE ?', ["%{$cleanTarget}%"])
                ->orWhereRaw('LOWER(name) LIKE ?', ["%{$cleanTarget}%"])
                ->orWhereRaw('LOWER(provider_url) LIKE ?', ["%{$cleanTarget}%"]);
        })->first();

        if ($cred) {
            return $cred;
        }

        // 5. Fallback: any email/smtp credential for the project
        if ($project) {
            $cred = Credential::where('project_id', $project->id)
                ->whereRaw("LOWER(type) IN ('email', 'smtp', 'mail', 'hosting', 'domain', 'private email')")
                ->first();

            if ($cred) {
                return $cred;
            }
        }

        // 6. Global fallback for any email/smtp credential
        return Credential::whereRaw("LOWER(type) IN ('email', 'smtp', 'mail', 'hosting', 'domain', 'private email')")->first();
    }

    /**
     * Detect SMTP host and port presets for Namecheap, Hostinger, Gmail, etc.
     */
    public function detectSmtpSettings(Credential $cred, string $fromEmail): array
    {
        $fields = is_array($cred->fields) ? $cred->fields : [];
        $nameClean = str_replace(' ', '', strtolower((string) $cred->name));
        $urlClean = str_replace(' ', '', strtolower((string) $cred->provider_url));
        $loginClean = strtolower((string) $cred->login);

        $host = $fields['smtp_host'] ?? null;
        $port = (int) ($fields['smtp_port'] ?? 0);
        $encryption = $fields['smtp_encryption'] ?? null;

        if (! $host) {
            if (str_contains($urlClean, 'privateemail') || str_contains($nameClean, 'privateemail') || str_contains($nameClean, 'namecheap')) {
                $host = 'mail.privateemail.com';
                $port = $port ?: 465;
                $encryption = $encryption ?: 'ssl';
            } elseif (str_contains($urlClean, 'hostinger') || str_contains($nameClean, 'hostinger')) {
                $host = 'smtp.hostinger.com';
                $port = $port ?: 465;
                $encryption = $encryption ?: 'ssl';
            } elseif (str_contains($urlClean, 'gmail') || str_contains($nameClean, 'gmail') || str_contains($loginClean, '@gmail.com') || str_contains($fromEmail, '@gmail.com')) {
                $host = 'smtp.gmail.com';
                $port = $port ?: 587;
                $encryption = $encryption ?: 'tls';
            } elseif (str_contains($urlClean, 'office365') || str_contains($urlClean, 'outlook') || str_contains($nameClean, 'outlook')) {
                $host = 'smtp.office365.com';
                $port = $port ?: 587;
                $encryption = $encryption ?: 'tls';
            } else {
                // Namecheap PrivateEmail is the default provider for custom domain emails
                $host = 'mail.privateemail.com';
                $port = $port ?: 465;
                $encryption = $encryption ?: 'ssl';
            }
        }

        if (! $port) {
            $port = 465;
        }

        if (! $encryption) {
            $encryption = $port === 465 ? 'ssl' : 'tls';
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

            Log::info('EmailReplyService: Attempting SMTP send', [
                'from' => $fromEmail,
                'to' => $toEmail,
                'host' => $settings['host'],
                'port' => $settings['port'],
                'username' => $settings['username'],
            ]);

            // Attempt 1: Default port/encryption
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
                Log::warning('EmailReplyService: Primary SMTP attempt failed, trying fallback port', [
                    'from' => $fromEmail,
                    'host' => $settings['host'],
                    'port' => $settings['port'],
                    'error' => $e->getMessage(),
                ]);

                // Attempt 2: Fallback port (587 TLS if 465 SSL failed, or vice-versa)
                try {
                    $altPort = $settings['port'] === 465 ? 587 : 465;
                    $altSsl = $altPort === 465;

                    $transportAlt = new EsmtpTransport(
                        $settings['host'],
                        $altPort,
                        $altSsl
                    );
                    $transportAlt->setUsername($settings['username']);
                    $transportAlt->setPassword($settings['password']);

                    $mailerAlt = new Mailer('custom_smtp_alt', app('view'), $transportAlt, app('events'));
                    $mailerAlt->raw($replyText, function ($message) use ($fromEmail, $senderName, $toEmail, $subject) {
                        $message->from($fromEmail, $senderName)
                            ->to($toEmail)
                            ->subject('Re: '.preg_replace('/^Re:\s*/i', '', $subject));
                    });

                    $sentVia = $settings['provider'];
                    $sentSuccess = true;
                } catch (\Throwable $e2) {
                    Log::error('EmailReplyService: Fallback SMTP attempt failed', [
                        'from' => $fromEmail,
                        'host' => $settings['host'],
                        'error' => $e2->getMessage(),
                    ]);
                }
            }
        } else {
            Log::warning('EmailReplyService: No matching Credential with password found for email', [
                'target' => $fromEmail,
                'project_id' => $project?->id,
            ]);
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
