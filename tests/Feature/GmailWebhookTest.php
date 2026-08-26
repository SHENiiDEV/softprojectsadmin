<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\Task;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GmailWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.gmail_webhook.secret', 'test-super-secret-key-12345');
        Storage::fake('private');
    }

    public function test_rejects_request_without_valid_signature(): void
    {
        $payload = [
            'thread_id' => 'thread_123',
            'message_id' => 'msg_123',
            'from' => 'Customer <customer@example.com>',
            'subject' => 'Need Refund',
        ];

        $response = $this->postJson('/api/v1/webhooks/gmail-alert', $payload, [
            'X-Signature-SHA256' => 'invalid-signature',
        ]);

        $response->assertStatus(401)
            ->assertJson(['status' => 'error']);
    }

    public function test_creates_ticket_and_crm_task_for_valid_webhook(): void
    {
        // Setup matching Project and Website
        $project = Project::factory()->create(['name' => 'Alpha Project']);
        $website = Website::create([
            'project_id' => $project->id,
            'name' => 'alpha-ou-1.net',
            'url' => 'https://alpha-ou-1.net',
            'status' => 'Live',
        ]);

        $payload = [
            'thread_id' => 'thread_abc_99',
            'message_id' => 'msg_xyz_01',
            'from' => 'Mihails <mihails.horolskis@gmail.com>',
            'to' => 'Support <support@alpha-ou-1.net>',
            'subject' => 'I want refund for my order',
            'date' => now()->toISOString(),
            'body_text' => 'Please refund me $50 immediately.',
            'categories' => ['refund'],
            'attachments' => [
                [
                    'filename' => 'receipt.png',
                    'mime_type' => 'image/png',
                    'size' => 1024,
                    'base64' => base64_encode('fake image data'),
                ],
            ],
        ];

        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, 'test-super-secret-key-12345');

        $response = $this->call(
            'POST',
            '/api/v1/webhooks/gmail-alert',
            [],
            [],
            [],
            [
                'HTTP_X-Signature-SHA256' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $jsonPayload
        );

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // Assert Ticket was created and matched to Project & Website
        $this->assertDatabaseHas('support_tickets', [
            'gmail_thread_id' => 'thread_abc_99',
            'customer_email' => 'mihails.horolskis@gmail.com',
            'project_id' => $project->id,
            'website_id' => $website->id,
        ]);

        $ticket = SupportTicket::where('gmail_thread_id', 'thread_abc_99')->firstOrFail();

        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'gmail_message_id' => 'msg_xyz_01',
        ]);

        $this->assertDatabaseHas('support_ticket_attachments', [
            'support_ticket_id' => $ticket->id,
            'original_filename' => 'receipt.png',
        ]);

        // Assert CRM Task was created
        $this->assertNotNull($ticket->task_id);
        $this->assertDatabaseHas('tasks', [
            'id' => $ticket->task_id,
            'project_id' => $project->id,
            'priority' => 'medium',
        ]);
    }

    public function test_appends_new_message_to_existing_ticket_thread(): void
    {
        $payload1 = [
            'thread_id' => 'thread_repeat_55',
            'message_id' => 'msg_01',
            'from' => 'mihails.horolskis@gmail.com',
            'to' => 'support@company.com',
            'subject' => 'Dispute',
            'body_text' => 'First message',
            'categories' => ['refund'],
            'attachments' => [],
        ];

        $payload2 = [
            'thread_id' => 'thread_repeat_55',
            'message_id' => 'msg_02',
            'from' => 'mihails.horolskis@gmail.com',
            'to' => 'support@company.com',
            'subject' => 'Dispute',
            'body_text' => 'Second message with proof',
            'categories' => ['chargeback'],
            'attachments' => [],
        ];

        $secret = 'test-super-secret-key-12345';

        // First message
        $json1 = json_encode($payload1);
        $this->call('POST', '/api/v1/webhooks/gmail-alert', [], [], [], [
            'HTTP_X-Signature-SHA256' => hash_hmac('sha256', $json1, $secret),
            'CONTENT_TYPE' => 'application/json',
        ], $json1);

        $this->assertEquals(1, SupportTicket::count());
        $this->assertEquals(1, SupportTicketMessage::count());

        // Second message in same thread
        $json2 = json_encode($payload2);
        $this->call('POST', '/api/v1/webhooks/gmail-alert', [], [], [], [
            'HTTP_X-Signature-SHA256' => hash_hmac('sha256', $json2, $secret),
            'CONTENT_TYPE' => 'application/json',
        ], $json2);

        // Should still have only 1 ticket, but 2 messages
        $this->assertEquals(1, SupportTicket::count());
        $this->assertEquals(2, SupportTicketMessage::count());

        $ticket = SupportTicket::first();
        $this->assertContains('refund', $ticket->categories);
        $this->assertContains('chargeback', $ticket->categories);

        // CRM Task priority should be upgraded to high for chargeback
        $task = Task::find($ticket->task_id);
        $this->assertEquals('high', $task->priority);
    }
}
