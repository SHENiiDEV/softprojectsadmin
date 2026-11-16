<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return ['message' => ['chat' => ['id' => 1], 'from' => ['id' => 1], 'text' => 'hello']];
    }

    public function test_rejects_request_when_secret_is_not_configured(): void
    {
        config(['services.telegram.webhook_secret' => null]);

        $this->postJson('/telegram/webhook', $this->payload())->assertStatus(503);
    }

    public function test_rejects_request_without_or_with_wrong_secret_header(): void
    {
        config(['services.telegram.webhook_secret' => 'right-secret']);

        $this->postJson('/telegram/webhook', $this->payload())->assertForbidden();

        $this->postJson('/telegram/webhook', $this->payload(), [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret',
        ])->assertForbidden();
    }

    public function test_accepts_request_with_valid_secret_header(): void
    {
        config(['services.telegram.webhook_secret' => 'right-secret']);

        $this->postJson('/telegram/webhook', $this->payload(), [
            'X-Telegram-Bot-Api-Secret-Token' => 'right-secret',
        ])->assertOk();
    }
}
