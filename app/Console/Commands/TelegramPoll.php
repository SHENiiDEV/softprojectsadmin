<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramPoll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll {--once : Run only once (useful for testing)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll Telegram API for updates and process them (Long Polling)';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegramService): int
    {
        $botToken = config('services.telegram.bot_token');
        if (!$botToken) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in configuration.');
            return 1;
        }

        $this->info('Starting Telegram polling...');
        $offset = 0;
        $once = $this->option('once');

        do {
            try {
                $url = "https://api.telegram.org/bot{$botToken}/getUpdates?offset={$offset}&timeout=5";
                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    $updates = $response->json('result') ?? [];
                    foreach ($updates as $update) {
                        $this->info("Processing update ID: " . $update['update_id']);
                        $telegramService->handleUpdate($update);
                        $offset = $update['update_id'] + 1;
                    }
                } else {
                    $this->error('Failed to fetch updates: ' . $response->body());
                    sleep(2);
                }
            } catch (\Exception $e) {
                $this->error('Error during polling: ' . $e->getMessage());
                sleep(2);
            }

            if ($once) {
                break;
            }

            usleep(100000); // 100ms
        } while (true);

        $this->info('Polling stopped.');
        return 0;
    }
}
