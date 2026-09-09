<?php

namespace Tests\Feature;

use App\Livewire\CalendarView;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_calendar_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('calendar'))
            ->assertStatus(200);
    }

    public function test_calendar_loads_tasks_with_due_dates(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $task = Task::create([
            'title' => 'Important Deadline Task',
            'status' => 'in_progress',
            'priority' => 'high',
            'due_date' => now()->addDays(2),
        ]);

        $this->actingAs($user);

        Livewire::test(CalendarView::class)
            ->assertSee('Important Deadline Task');
    }

    public function test_can_reschedule_task_due_date_via_calendar(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $task = Task::create([
            'title' => 'Reschedule Target Task',
            'status' => 'todo',
            'priority' => 'medium',
            'due_date' => '2026-09-10',
        ]);

        $this->actingAs($user);

        Livewire::test(CalendarView::class)
            ->call('updateTaskDueDate', $task->id, '2026-09-20');

        $this->assertEquals('2026-09-20', $task->fresh()->due_date->format('Y-m-d'));

        $this->assertDatabaseHas('activity_logs', [
            'task_id' => $task->id,
            'action' => 'task_rescheduled',
        ]);
    }

    public function test_can_filter_calendar_by_project_and_assignee(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $projectA = Project::factory()->create(['name' => 'Alpha Corp']);
        $projectB = Project::factory()->create(['name' => 'Beta Ltd']);

        Task::create([
            'title' => 'Alpha Task',
            'project_id' => $projectA->id,
            'due_date' => now()->addDay(),
            'status' => 'todo',
            'priority' => 'low',
        ]);

        Task::create([
            'title' => 'Beta Task',
            'project_id' => $projectB->id,
            'due_date' => now()->addDay(),
            'status' => 'todo',
            'priority' => 'low',
        ]);

        $this->actingAs($user);

        Livewire::test(CalendarView::class)
            ->set('filterProject', (string) $projectA->id)
            ->assertSee('Alpha Task')
            ->assertDontSee('Beta Task');
    }
}
