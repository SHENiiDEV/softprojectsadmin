<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Gmail as GoogleGmail;
use Google\Service\Gmail\Message;
use Google\Service\Gmail\MessagePart;
use Google\Service\Gmail\WatchRequest;
use Illuminate\Support\Facades\Log;

class GmailSyncService
{
    protected GoogleClient $client;

    protected ?GoogleGmail $gmail = null;

    public function __construct()
    {
        $this->client = new GoogleClient;
        $this->client->setClientId((string) config('services.google.client_id'));
        $this->client->setClientSecret((string) config('services.google.client_secret'));

        $refreshToken = config('services.google.refresh_token');
        if ($refreshToken) {
            $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        }
    }

    public function getGmailService(): GoogleGmail
    {
        if (! $this->gmail) {
            $this->gmail = new GoogleGmail($this->client);
        }

        return $this->gmail;
    }

    /**
     * Subscribe Gmail mailbox to Google Cloud Pub/Sub topic.
     *
     * @return array{historyId: string, expiration: string}
     */
    public function watch(string $userId = 'me'): array
    {
        $gmail = $this->getGmailService();

        $watchRequest = new WatchRequest;
        $watchRequest->setTopicName((string) config('services.google.topic_name'));
        $watchRequest->setLabelIds(['INBOX', 'SENT']);

        $response = $gmail->users->watch($userId, $watchRequest);

        return [
            'historyId' => (string) $response->getHistoryId(),
            'expiration' => (string) $response->getExpiration(),
        ];
    }

    /**
     * Fetch history records starting from a given historyId.
     *
     * @return array<array{id: string, threadId: string}>
     */
    public function fetchAddedMessages(string $startHistoryId, string $userId = 'me'): array
    {
        $gmail = $this->getGmailService();
        $addedMessages = [];

        try {
            $response = $gmail->users_history->listUsersHistory($userId, [
                'startHistoryId' => $startHistoryId,
                'historyTypes' => ['messageAdded'],
            ]);

            $histories = $response->getHistory() ?? [];
            foreach ($histories as $history) {
                $messagesAdded = $history->getMessagesAdded() ?? [];
                foreach ($messagesAdded as $item) {
                    $msg = $item->getMessage();
                    if ($msg && $msg->getId()) {
                        $addedMessages[] = [
                            'id' => $msg->getId(),
                            'threadId' => $msg->getThreadId(),
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to fetch Gmail history', [
                'startHistoryId' => $startHistoryId,
                'error' => $e->getMessage(),
            ]);
        }

        return $addedMessages;
    }

    /**
     * Fetch full Gmail message payload by message ID.
     */
    public function getMessage(string $messageId, string $userId = 'me'): ?Message
    {
        $gmail = $this->getGmailService();

        try {
            return $gmail->users_messages->get($userId, $messageId, ['format' => 'full']);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch Gmail message', [
                'messageId' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Parse raw Gmail message into structured array data.
     *
     * @return array{
     *     id: string,
     *     threadId: string,
     *     from: string,
     *     to: string,
     *     subject: string,
     *     date: ?string,
     *     body: string,
     *     attachments: array<array{filename: string, mimeType: string, attachmentId: string, size: int}>,
     *     labelIds: array<string>
     * }
     */
    public function parseMessagePayload(Message $message): array
    {
        $payload = $message->getPayload();
        $headers = $payload ? $payload->getHeaders() : [];

        $headersMap = [];
        foreach ($headers as $h) {
            $headersMap[strtolower($h->getName())] = $h->getValue();
        }

        $from = $headersMap['from'] ?? '';
        $to = $headersMap['to'] ?? '';
        $subject = $headersMap['subject'] ?? 'No Subject';
        $date = $headersMap['date'] ?? null;

        $bodyText = '';
        $attachments = [];

        if ($payload) {
            $this->extractParts($payload, $bodyText, $attachments);
        }

        return [
            'id' => (string) $message->getId(),
            'threadId' => (string) $message->getThreadId(),
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'date' => $date,
            'body' => trim($bodyText),
            'attachments' => $attachments,
            'labelIds' => $message->getLabelIds() ?? [],
        ];
    }

    /**
     * Recursively extract text body and attachment metadata from MIME payload.
     */
    private function extractParts(MessagePart $part, string &$bodyText, array &$attachments): void
    {
        $filename = $part->getFilename();
        $body = $part->getBody();
        $mimeType = $part->getMimeType();

        if (! empty($filename) && $body && $body->getAttachmentId()) {
            $attachments[] = [
                'filename' => $filename,
                'mimeType' => $mimeType ?: 'application/octet-stream',
                'attachmentId' => $body->getAttachmentId(),
                'size' => (int) $body->getSize(),
            ];
        }

        if ($mimeType === 'text/plain' && $body && $body->getData()) {
            $decoded = base64_decode(strtr($body->getData(), '-_', '+/'));
            if ($decoded) {
                $bodyText .= "\n".$decoded;
            }
        } elseif ($mimeType === 'text/html' && empty($bodyText) && $body && $body->getData()) {
            $decoded = base64_decode(strtr($body->getData(), '-_', '+/'));
            if ($decoded) {
                $bodyText .= "\n".strip_tags($decoded);
            }
        }

        $parts = $part->getParts();
        if ($parts) {
            foreach ($parts as $subPart) {
                $this->extractParts($subPart, $bodyText, $attachments);
            }
        }
    }

    /**
     * Download attachment bytes from Gmail API.
     */
    public function downloadAttachment(string $messageId, string $attachmentId, string $userId = 'me'): ?string
    {
        $gmail = $this->getGmailService();

        try {
            $attachment = $gmail->users_messages_attachments->get($userId, $messageId, $attachmentId);
            $data = $attachment->getData();

            if (! $data) {
                return null;
            }

            return base64_decode(strtr($data, '-_', '+/'));
        } catch (\Throwable $e) {
            Log::error('Failed to download Gmail attachment', [
                'messageId' => $messageId,
                'attachmentId' => $attachmentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send an email reply using Gmail API within the same thread.
     */
    public function sendReplyEmail(
        string $toEmail,
        string $subject,
        string $bodyText,
        string $threadId,
        ?string $inReplyToMessageId = null,
        ?string $fromEmail = null,
        ?string $fromName = null
    ): ?string {
        try {
            $gmail = $this->getGmailService();

            $fromStr = $fromEmail ? ($fromName ? "{$fromName} <{$fromEmail}>" : $fromEmail) : null;

            $headers = [
                "To: {$toEmail}",
                'Subject: Re: '.preg_replace('/^Re:\s*/i', '', $subject),
                'Content-Type: text/plain; charset=UTF-8',
                'MIME-Version: 1.0',
            ];

            if ($fromStr) {
                $headers[] = "From: {$fromStr}";
            }

            if ($inReplyToMessageId) {
                $headers[] = "In-Reply-To: {$inReplyToMessageId}";
                $headers[] = "References: {$inReplyToMessageId}";
            }

            $rawMessageString = implode("\r\n", $headers)."\r\n\r\n".$bodyText;
            $mimeMessage = rtrim(strtr(base64_encode($rawMessageString), '+/', '-_'), '=');

            $message = new Message;
            $message->setRaw($mimeMessage);
            $message->setThreadId($threadId);

            $sentMessage = $gmail->users_messages->send('me', $message);

            return $sentMessage->getId();
        } catch (\Throwable $e) {
            Log::error('Failed to send Gmail reply email', [
                'to' => $toEmail,
                'threadId' => $threadId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
