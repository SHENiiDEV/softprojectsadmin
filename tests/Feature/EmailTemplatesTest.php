<?php

namespace Tests\Feature;

use App\Livewire\Settings\EmailTemplates as EmailTemplatesComponent;
use App\Livewire\Tasks\KanbanBoard;
use App\Models\EmailTemplate;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmailTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_manage_email_templates(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EmailTemplatesComponent::class)
            ->call('openModal')
            ->set('name', '💳 Refund Confirmation')
            ->set('category', 'refunds')
            ->set('subject', 'Re: Refund Processed')
            ->set('body_text', 'Hello, your refund for ticket #{ticket_number} has been processed.')
            ->call('saveTemplate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('email_templates', [
            'name' => '💳 Refund Confirmation',
            'category' => 'refunds',
        ]);
    }

    public function test_placeholder_rendering(): void
    {
        $template = EmailTemplate::create([
            'name' => 'Test Template',
            'category' => 'general',
            'body_text' => 'Hello {client_email}, welcome to {company_name} on {website_url}. Ticket #{ticket_number}',
        ]);

        $rendered = $template->renderBody([
            'client_email' => 'john@client.com',
            'company_name' => 'Acme Corp',
            'website_url' => 'https://acme.com',
            'ticket_number' => 'TICK-101',
        ]);

        $this->assertEquals('Hello john@client.com, welcome to Acme Corp on https://acme.com. Ticket #TICK-101', $rendered);
    }

    public function test_can_apply_template_in_kanban_task_reply_modal(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Sivora Limited']);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Customer Inquiry',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $ticket = SupportTicket::create([
            'project_id' => $project->id,
            'task_id' => $task->id,
            'customer_email' => 'client@external.com',
            'subject' => 'Refund question',
            'status' => 'new',
            'gmail_thread_id' => 'thread_xyz',
        ]);

        $template = EmailTemplate::create([
            'name' => 'Refund Notice',
            'category' => 'refunds',
            'body_text' => 'Dear {client_email}, your refund for {company_name} is done.',
        ]);

        Livewire::actingAs($user)
            ->test(KanbanBoard::class)
            ->call('openTaskModal', $task->id)
            ->set('selectedEmailTemplateId', (string) $template->id)
            ->call('applyEmailTemplate')
            ->assertSet('emailReplyBody', 'Dear client@external.com, your refund for Sivora Limited is done.');
    }
}
