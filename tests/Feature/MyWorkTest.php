<?php

namespace Tests\Feature;

use App\Livewire\MyWork;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MyWorkTest extends TestCase
{
    use RefreshDatabase;

    public function test_displays_latest_comment_on_task_card(): void
    {
        $user = User::factory()->create();

        $task = Task::create([
            'title' => 'Task with Latest Comment',
            'status' => 'in_progress',
            'priority' => 'high',
            'assigned_to' => $user->id,
        ]);

        Comment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'content' => 'First older comment',
            'created_at' => now()->subHours(5),
        ]);

        Comment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'content' => 'Latest fresh comment text',
            'created_at' => now()->subMinute(),
        ]);

        $this->actingAs($user);

        Livewire::test(MyWork::class)
            ->assertSee('Task with Latest Comment')
            ->assertSee('Latest fresh comment text')
            ->assertSee($user->name);
    }

    public function test_can_sort_tasks_by_latest_comment(): void
    {
        $user = User::factory()->create();

        $taskA = Task::create([
            'title' => 'Task A (Older Comment)',
            'status' => 'todo',
            'priority' => 'medium',
            'assigned_to' => $user->id,
        ]);

        $taskB = Task::create([
            'title' => 'Task B (Newer Comment)',
            'status' => 'todo',
            'priority' => 'medium',
            'assigned_to' => $user->id,
        ]);

        Comment::create([
            'task_id' => $taskA->id,
            'user_id' => $user->id,
            'content' => 'Comment on Task A',
            'created_at' => now()->subDays(2),
        ]);

        Comment::create([
            'task_id' => $taskB->id,
            'user_id' => $user->id,
            'content' => 'Comment on Task B',
            'created_at' => now()->subHour(),
        ]);

        $this->actingAs($user);

        Livewire::test(MyWork::class)
            ->set('sortBy', 'latest_comment')
            ->assertSeeInOrder(['Task B (Newer Comment)', 'Task A (Older Comment)']);
    }
}
