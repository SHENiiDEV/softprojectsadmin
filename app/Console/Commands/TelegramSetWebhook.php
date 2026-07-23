<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramSetWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook {url? : The webhook URL (optional, defaults to production domain)} {--delete : Delete the webhook instead}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set, delete, or check the Telegram Bot API Webhook URL';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $botToken = config('services.telegram.bot_token');
        if (!$botToken) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in configuration.');
            return 1;
        }

        if ($this->option('delete')) {
            $this->info('Deleting Telegram Webhook...');
            try {
                $response = Http::post("https://api.telegram.org/bot{$botToken}/deleteWebhook");
                if ($response->successful()) {
                    $this->info('Webhook deleted successfully!');
                    $this->line(json_encode($response->json(), JSON_PRETTY_PRINT));
                    return 0;
                } else {
                    $this->error('Failed to delete webhook: ' . $response->body());
                    return 1;
                }
            } catch (\Exception $e) {
                $this->error('Error occurred: ' . $e->getMessage());
                return 1;
            }
        }

        $url = $this->argument('url');

        if (!$url) {
            $url = "https://soft-projects.io/telegram/webhook";
        }

        $this->info("Setting Telegram Webhook to: {$url} ...");

        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/setWebhook", [
                'url' => $url,
            ]);

            if ($response->successful()) {
                $this->info('Webhook request sent successfully!');
                $this->line(json_encode($response->json(), JSON_PRETTY_PRINT));

                // Fetch webhook info to verify
                $this->info('Verifying Webhook Info...');
                $infoResponse = Http::get("https://api.telegram.org/bot{$botToken}/getWebhookInfo");
                if ($infoResponse->successful()) {
                    $this->line(json_encode($infoResponse->json(), JSON_PRETTY_PRINT));
                }
                return 0;
            } else {
                $this->error('Failed to set webhook: ' . $response->body());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Error occurred: ' . $e->getMessage());
            return 1;
        }
    }
}
