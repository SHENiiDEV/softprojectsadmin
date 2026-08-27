<?php

namespace Tests\Feature;

use App\Livewire\Tasks\KanbanBoard;
use App\Models\Credential;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\Task;
use App\Models\User;
use App\Services\EmailReplyService;
use App\Services\GmailSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class ReplyToClientEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_detect_namecheap_privateemail_credentials_and_send_reply(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        // Create Namecheap PrivateEmail credentials in Credential Vault
        Credential::create([
            'project_id' => $project->id,
            'name' => 'Namecheap PrivateEmail',
            'type' => 'email',
            'provider_url' => 'https://mail.privateemail.com',
            'login' => 'info@sivora.co.uk',
            'password' => 'SecretPassword123!',
        ]);

        $service = app(EmailReplyService::class);
        $cred = $service->resolveCredential($project, 'info@sivora.co.uk');

        $this->assertNotNull($cred);
        $this->assertEquals('info@sivora.co.uk', $cred->login);

        $settings = $service->detectSmtpSettings($cred, 'info@sivora.co.uk');
        $this->assertEquals('mail.privateemail.com', $settings['host']);
        $this->assertEquals('Namecheap PrivateEmail', $settings['provider']);
    }

    public function test_can_detect_hostinger_mail_credentials(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        Credential::create([
            'project_id' => $project->id,
            'name' => 'Hostinger Webmail',
            'type' => 'email',
            'provider_url' => 'https://mail.hostinger.com',
            'login' => 'support@clientdomain.com',
            'password' => 'HostingerPass123!',
        ]);

        $service = app(EmailReplyService::class);
        $cred = $service->resolveCredential($project, 'support@clientdomain.com');

        $this->assertNotNull($cred);
        $settings = $service->detectSmtpSettings($cred, 'support@clientdomain.com');
        $this->assertEquals('smtp.hostinger.com', $settings['host']);
        $this->assertEquals('Hostinger Mail', $settings['provider']);
    }

    public function test_send_client_email_reply_creates_outgoing_message_and_comment(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Email Ticket: Issue with payment gateway',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $ticket = SupportTicket::create([
            'project_id' => $project->id,
            'task_id' => $task->id,
            'customer_email' => 'client@external.com',
            'subject' => 'Issue with payment gateway',
            'status' => 'new',
            'gmail_thread_id' => 'thread_abc123',
        ]);

        SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'gmail_message_id' => 'msg_111',
            'from' => 'client@external.com',
            'to' => 'info@sivora.co.uk',
            'is_outgoing' => false,
            'body_text' => 'Please help with gateway setup',
            'sent_at' => now(),
        ]);

        // Mock GmailSyncService reply method
        $this->mock(GmailSyncService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendReplyEmail')
                ->once()
                ->andReturn('gmail_sent_999');
        });

        Livewire::actingAs($user)
            ->test(KanbanBoard::class)
            ->call('openTaskModal', $task->id)
            ->set('emailReplyBody', 'Thank you for reaching out. We have updated your gateway settings.')
            ->call('sendClientEmailReply')
            ->assertHasNoErrors();

        // Assert outgoing SupportTicketMessage created
        $outgoing = SupportTicketMessage::where('support_ticket_id', $ticket->id)
            ->where('is_outgoing', true)
            ->first();

        $this->assertNotNull($outgoing);
        $this->assertStringContainsString('Thank you for reaching out', $outgoing->body_text);

        // Assert ticket status updated
        $this->assertEquals('answered', $ticket->fresh()->status);

        // Assert comment created on task
        $this->assertDatabaseHas('comments', [
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);
    }
}
