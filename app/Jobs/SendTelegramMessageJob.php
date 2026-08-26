<?php

namespace App\Jobs;

use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 10;

    /**
     * The number of seconds to wait before retrying a job that encountered an exception.
     */
    public int $backoff = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int|string $chatId,
        public string $text,
        public ?array $replyMarkup = null
    ) {}

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [new RateLimited('telegram-messages')];
    }

    /**
     * Execute the job.
     */
    public function handle(TelegramService $telegramService): void
    {
        $telegramService->sendMessage($this->chatId, $this->text, $this->replyMarkup);
    }
}
