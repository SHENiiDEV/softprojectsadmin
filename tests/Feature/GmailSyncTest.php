<?php

namespace Tests\Feature;

use App\Jobs\ProcessGmailSyncJob;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\Task;
use App\Models\Website;
use App\Services\GmailSyncService;
use Google\Service\Gmail\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GmailSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_pubsub_webhook_decodes_base64_and_dispatches_job(): void
    {
        Queue::fake();

        $payload = [
            'message' => [
                'data' => base64_encode(json_encode([
                    'emailAddress' => 'user@domain.com',
                    'historyId' => '12345678',
                ])),
                'messageId' => 'msg_pubsub_001',
            ],
        ];

        $response = $this->postJson('/api/v1/gmail/pubsub-webhook', $payload);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success', 'historyId' => '12345678']);

        Queue::assertPushed(ProcessGmailSyncJob::class, function ($job) {
            return $job->historyId === '12345678';
        });
    }

    public function test_sync_job_creates_ticket_and_task_for_keyword_email(): void
    {
        $project = Project::factory()->create(['name' => 'Sivora LTD']);
        $website = Website::create([
            'project_id' => $project->id,
            'name' => 'sivora.co.uk',
            'url' => 'https://sivora.co.uk',
            'status' => 'Live',
        ]);

        $mockService = \Mockery::mock(GmailSyncService::class);
        $mockService->shouldReceive('fetchAddedMessages')
            ->once()
            ->with('1001')
            ->andReturn([
                ['id' => 'msg_100', 'threadId' => 'thread_100'],
            ]);

        $rawMsg = new Message;
        $rawMsg->setId('msg_100');
        $rawMsg->setThreadId('thread_100');

        $mockService->shouldReceive('getMessage')
            ->once()
            ->with('msg_100')
            ->andReturn($rawMsg);

        $mockService->shouldReceive('parseMessagePayload')
            ->once()
            ->with($rawMsg)
            ->andReturn([
                'id' => 'msg_100',
                'threadId' => 'thread_100',
                'from' => 'Customer <mihails.horolskis@gmail.com>',
                'to' => 'Support <info@sivora.co.uk>',
                'subject' => 'Request refund for purchase',
                'date' => now()->toISOString(),
                'body' => 'I want refund for my order #1234',
                'attachments' => [],
                'labelIds' => ['INBOX'],
            ]);

        $job = new ProcessGmailSyncJob('1001');
        $job->handle($mockService);

        // Assert ticket created
        $this->assertDatabaseHas('support_tickets', [
            'gmail_thread_id' => 'thread_100',
            'customer_email' => 'mihails.horolskis@gmail.com',
            'status' => 'open',
            'project_id' => $project->id,
            'website_id' => $website->id,
        ]);

        $ticket = SupportTicket::where('gmail_thread_id', 'thread_100')->firstOrFail();

        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'gmail_message_id' => 'msg_100',
            'is_outgoing' => false,
        ]);

        $this->assertNotNull($ticket->task_id);
        $this->assertDatabaseHas('tasks', [
            'id' => $ticket->task_id,
            'project_id' => $project->id,
        ]);
    }

    public function test_sync_job_updates_ticket_status_to_customer_replied_and_answered(): void
    {
        $ticket = SupportTicket::create([
            'gmail_thread_id' => 'thread_sync_200',
            'customer_email' => 'client@domain.com',
            'recipient_email' => 'support@company.com',
            'subject' => 'Dispute order',
            'status' => 'open',
            'categories' => ['refund'],
        ]);

        $task = Task::create([
            'title' => '📩 [Refund] Dispute order',
            'description' => 'Test task',
            'status' => 'todo',
            'priority' => 'medium',
        ]);
        $ticket->update(['task_id' => $task->id]);

        $mockService = \Mockery::mock(GmailSyncService::class);

        // 1. Client sends follow-up reply
        $mockService->shouldReceive('fetchAddedMessages')
            ->once()
            ->with('2001')
            ->andReturn([['id' => 'msg_client_reply', 'threadId' => 'thread_sync_200']]);

        $rawClientMsg = new Message;
        $mockService->shouldReceive('getMessage')->with('msg_client_reply')->andReturn($rawClientMsg);
        $mockService->shouldReceive('parseMessagePayload')->with($rawClientMsg)->andReturn([
            'id' => 'msg_client_reply',
            'threadId' => 'thread_sync_200',
            'from' => 'client@domain.com',
            'to' => 'support@company.com',
            'subject' => 'Re: Dispute order',
            'date' => now()->toISOString(),
            'body' => 'Here is my receipt photo as proof',
            'attachments' => [],
            'labelIds' => ['INBOX'],
        ]);

        (new ProcessGmailSyncJob('2001'))->handle($mockService);

        $this->assertEquals('customer_replied', $ticket->fresh()->status);
        $this->assertDatabaseHas('comments', ['task_id' => $task->id]);

        // 2. Operator sends outgoing reply
        $mockService->shouldReceive('fetchAddedMessages')
            ->once()
            ->with('2002')
            ->andReturn([['id' => 'msg_operator_reply', 'threadId' => 'thread_sync_200']]);

        $rawOperatorMsg = new Message;
        $mockService->shouldReceive('getMessage')->with('msg_operator_reply')->andReturn($rawOperatorMsg);
        $mockService->shouldReceive('parseMessagePayload')->with($rawOperatorMsg)->andReturn([
            'id' => 'msg_operator_reply',
            'threadId' => 'thread_sync_200',
            'from' => 'support@company.com',
            'to' => 'client@domain.com',
            'subject' => 'Re: Dispute order',
            'date' => now()->toISOString(),
            'body' => 'We have processed your refund request. Thank you!',
            'attachments' => [],
            'labelIds' => ['SENT'],
        ]);

        (new ProcessGmailSyncJob('2002'))->handle($mockService);

        $this->assertEquals('answered', $ticket->fresh()->status);

        $this->assertDatabaseHas('support_ticket_messages', [
            'gmail_message_id' => 'msg_operator_reply',
            'is_outgoing' => true,
        ]);
    }
}
