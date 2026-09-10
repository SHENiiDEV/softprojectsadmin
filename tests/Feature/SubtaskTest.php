<?php

namespace Tests\Feature;

use App\Livewire\MyWork;
use App\Livewire\Tasks\KanbanBoard;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubtaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_subtask_associated_with_parent_task(): void
    {
        $user = User::factory()->create();

        $parent = Task::create([
            'title' => 'Parent Main Task',
            'status' => 'in_progress',
            'priority' => 'high',
        ]);

        $this->actingAs($user);

        Livewire::test(KanbanBoard::class)
            ->set('editingTaskId', $parent->id)
            ->set('newSubtaskTitle', 'Design subtask element')
            ->call('createSubtask')
            ->assertSet('newSubtaskTitle', '');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Design subtask element',
            'parent_id' => $parent->id,
            'status' => 'todo',
        ]);

        $subtask = Task::where('parent_id', $parent->id)->first();
        $this->assertTrue($subtask->isSubtask());
        $this->assertEquals($parent->id, $subtask->parent->id);
    }

    public function test_subtask_progress_attribute_calculates_correctly(): void
    {
        $parent = Task::create([
            'title' => 'Parent Task',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        Task::create([
            'title' => 'Sub 1',
            'parent_id' => $parent->id,
            'status' => 'done',
            'priority' => 'medium',
        ]);

        Task::create([
            'title' => 'Sub 2',
            'parent_id' => $parent->id,
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $parent->refresh();

        $progress = $parent->subtask_progress;
        $this->assertEquals(2, $progress['total']);
        $this->assertEquals(1, $progress['completed']);
        $this->assertEquals(50, $progress['percentage']);
    }

    public function test_can_toggle_subtask_status(): void
    {
        $user = User::factory()->create();

        $parent = Task::create([
            'title' => 'Parent Task',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $subtask = Task::create([
            'title' => 'Subtask 1',
            'parent_id' => $parent->id,
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $this->actingAs($user);

        Livewire::test(KanbanBoard::class)
            ->call('toggleSubtaskStatus', $subtask->id);

        $this->assertEquals('done', $subtask->fresh()->status);

        Livewire::test(KanbanBoard::class)
            ->call('toggleSubtaskStatus', $subtask->id);

        $this->assertEquals('todo', $subtask->fresh()->status);
    }

    public function test_can_delete_subtask(): void
    {
        $user = User::factory()->create();

        $parent = Task::create([
            'title' => 'Parent Task',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $subtask = Task::create([
            'title' => 'Subtask 1',
            'parent_id' => $parent->id,
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $this->actingAs($user);

        Livewire::test(KanbanBoard::class)
            ->call('deleteSubtask', $subtask->id);

        $this->assertDatabaseMissing('tasks', ['id' => $subtask->id]);
    }

    public function test_can_assign_subtask_to_another_user_and_toggle_status_in_my_work(): void
    {
        $userA = User::factory()->create(['name' => 'User A']);
        $userB = User::factory()->create(['name' => 'User B']);

        $parent = Task::create([
            'title' => 'Main Task for User A',
            'status' => 'in_progress',
            'priority' => 'high',
            'assigned_to' => $userA->id,
        ]);
        $parent->syncAssignees([$userA->id]);

        $this->actingAs($userA);

        Livewire::test(KanbanBoard::class)
            ->set('editingTaskId', $parent->id)
            ->set('newSubtaskTitle', 'Backend Subtask for User B')
            ->set('newSubtaskAssignee', (string) $userB->id)
            ->call('createSubtask');

        $subtask = Task::where('parent_id', $parent->id)->first();
        $this->assertNotNull($subtask);
        $this->assertEquals($userB->id, $subtask->assigned_to);

        // Act as User B on MyWork page
        $this->actingAs($userB);

        Livewire::test(MyWork::class)
            ->call('changeStatus', $subtask->id, 'done');

        $this->assertEquals('done', $subtask->fresh()->status);
        $this->assertEquals(100, $parent->fresh()->subtask_progress['percentage']);
    }
}
