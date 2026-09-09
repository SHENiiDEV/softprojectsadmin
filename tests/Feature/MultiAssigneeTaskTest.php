<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramMessageJob;
use App\Livewire\Tasks\KanbanBoard;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class MultiAssigneeTaskTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent1;
    protected User $agent2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Admin User']);
        $this->admin->assignRole('admin');

        $this->agent1 = User::factory()->create([
            'name' => 'Agent One',
            'telegram_id' => '111111',
        ]);
        $this->agent1->assignRole('worker');

        $this->agent2 = User::factory()->create([
            'name' => 'Agent Two',
            'telegram_id' => '222222',
        ]);
        $this->agent2->assignRole('worker');

        $this->actingAs($this->admin);
    }

    public function test_can_assign_two_agents_to_a_task(): void
    {
        Livewire::test(KanbanBoard::class)
            ->set('taskTitle', 'Multi-Assignee Feature Test')
            ->set('taskPriority', 'high')
            ->set('taskStatus', 'todo')
            ->set('taskAssignees', [(string)$this->agent1->id, (string)$this->agent2->id])
            ->call('saveTask')
            ->assertHasNoErrors();

        $task = Task::where('title', 'Multi-Assignee Feature Test')->first();
        $this->assertNotNull($task);
        $this->assertEquals($this->agent1->id, $task->assigned_to);
        $this->assertCount(2, $task->assignees);
        $this->assertTrue($task->assignees->contains($this->agent1));
        $this->assertTrue($task->assignees->contains($this->agent2));
    }

    public function test_notifications_sent_to_both_assignees_on_comment(): void
    {
        Queue::fake();

        $task = Task::create([
            'title' => 'Joint Review Task',
            'status' => 'in_progress',
            'priority' => 'medium',
            'creator_id' => $this->admin->id,
        ]);
        $task->syncAssignees([$this->agent1->id, $this->agent2->id]);

        $comment = Comment::create([
            'task_id' => $task->id,
            'user_id' => $this->admin->id,
            'content' => 'Please both review this submission.',
        ]);

        \App\Services\NotificationService::sendNewCommentNotification($comment);

        Queue::assertPushed(SendTelegramMessageJob::class, function ($job) {
            return (string)$job->chatId === '111111';
        });

        Queue::assertPushed(SendTelegramMessageJob::class, function ($job) {
            return (string)$job->chatId === '222222';
        });
    }

    public function test_both_assignees_can_see_task_on_kanban_and_my_work(): void
    {
        $task = Task::create([
            'title' => 'Joint Project Task',
            'status' => 'todo',
            'priority' => 'high',
        ]);
        $task->syncAssignees([$this->agent1->id, $this->agent2->id]);

        // Test Agent 1 (Primary)
        $this->actingAs($this->agent1);
        Livewire::test(KanbanBoard::class)
            ->assertSee('Joint Project Task');

        Livewire::test(\App\Livewire\MyWork::class)
            ->assertSee('Joint Project Task');

        // Test Agent 2 (Secondary)
        $this->actingAs($this->agent2);
        Livewire::test(KanbanBoard::class)
            ->assertSee('Joint Project Task');

        Livewire::test(\App\Livewire\MyWork::class)
            ->assertSee('Joint Project Task');
    }
}
