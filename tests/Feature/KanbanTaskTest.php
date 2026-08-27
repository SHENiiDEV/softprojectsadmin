<?php

namespace Tests\Feature;

use App\Livewire\Tasks\KanbanBoard;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class KanbanTaskTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected $workerUser;

    protected $curatorUser;

    protected $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(RolesAndPermissionsSeeder::class);

        // Create users
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $this->workerUser = User::factory()->create();
        $this->workerUser->assignRole('worker');

        $this->curatorUser = User::factory()->create();
        $this->curatorUser->assignRole('curator');

        // Create project
        $this->project = Project::factory()->create([
            'manager_id' => $this->adminUser->id,
        ]);
    }

    public function test_kanban_board_route_requires_auth(): void
    {
        $response = $this->get('/tasks');
        $response->assertRedirect('/login');
    }

    public function test_kanban_board_page_loads_successfully_for_authorized_user(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/tasks');
        $response->assertStatus(200);
        $response->assertSeeLivewire(KanbanBoard::class);
    }

    public function test_admin_can_create_task(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(KanbanBoard::class)
            ->set('taskTitle', 'New Task')
            ->set('taskDescription', 'Task description')
            ->set('taskProject', $this->project->id)
            ->set('taskAssignee', $this->workerUser->id)
            ->set('taskPriority', 'high')
            ->set('taskStatus', 'todo')
            ->call('saveTask')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'project_id' => $this->project->id,
            'assigned_to' => $this->workerUser->id,
            'priority' => 'high',
            'status' => 'todo',
        ]);
    }

    public function test_admin_can_create_global_task_without_project(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(KanbanBoard::class)
            ->set('taskTitle', 'Global Task')
            ->set('taskProject', '') // Nullable
            ->set('taskAssignee', $this->workerUser->id)
            ->set('taskPriority', 'low')
            ->set('taskStatus', 'todo')
            ->call('saveTask')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Global Task',
            'project_id' => null,
            'assigned_to' => $this->workerUser->id,
            'priority' => 'low',
        ]);
    }

    public function test_curator_cannot_create_task(): void
    {
        Livewire::actingAs($this->curatorUser)
            ->test(KanbanBoard::class)
            ->set('taskTitle', 'Curator Task')
            ->set('taskPriority', 'medium')
            ->set('taskStatus', 'todo')
            ->call('saveTask');

        $this->assertDatabaseMissing('tasks', [
            'title' => 'Curator Task',
        ]);
    }

    public function test_worker_can_update_status_of_assigned_task(): void
    {
        $task = Task::create([
            'title' => 'Worker Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->adminUser->id,
            'assigned_to' => $this->workerUser->id,
            'status' => 'todo',
        ]);

        Livewire::actingAs($this->workerUser)
            ->test(KanbanBoard::class)
            ->call('updateTaskStatus', $task->id, 'in_progress');

        $this->assertEquals('in_progress', $task->fresh()->status);
    }

    public function test_worker_cannot_update_status_of_unassigned_task(): void
    {
        $task = Task::create([
            'title' => 'Other Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->adminUser->id,
            'assigned_to' => $this->adminUser->id, // Assigned to admin
            'status' => 'todo',
        ]);

        Livewire::actingAs($this->workerUser)
            ->test(KanbanBoard::class)
            ->call('updateTaskStatus', $task->id, 'in_progress');

        $this->assertEquals('todo', $task->fresh()->status);
    }

    public function test_task_status_can_be_updated_via_drag_and_drop(): void
    {
        $task = Task::create([
            'title' => 'Drag Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->adminUser->id,
            'assigned_to' => $this->workerUser->id,
            'status' => 'todo',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(KanbanBoard::class)
            ->call('updateTaskStatus', $task->id, 'review');

        $this->assertEquals('review', $task->fresh()->status);
    }

    public function test_task_can_have_file_attachments(): void
    {
        Storage::fake('public');

        $task = Task::create([
            'title' => 'File Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->adminUser->id,
            'status' => 'todo',
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 500); // 500KB

        Livewire::actingAs($this->adminUser)
            ->test(KanbanBoard::class)
            ->call('openTaskModal', $task->id)
            ->set('attachments', [$file])
            ->call('saveTask')
            ->assertHasNoErrors();

        $task = $task->fresh();
        $this->assertEquals(1, $task->getMedia('attachments')->count());
        $this->assertEquals('document.pdf', $task->getMedia('attachments')->first()->file_name);
    }

    public function test_global_tasks_filter_works_successfully(): void
    {
        // 1. Create a task with a project
        Task::create([
            'title' => 'Project Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->adminUser->id,
            'status' => 'todo',
        ]);

        // 2. Create a global task (without project)
        Task::create([
            'title' => 'Global Task',
            'project_id' => null,
            'creator_id' => $this->adminUser->id,
            'status' => 'todo',
        ]);

        // 3. Test filterProject set to 'global'
        Livewire::actingAs($this->adminUser)
            ->test(KanbanBoard::class)
            ->set('filterProject', 'global')
            ->assertHasNoErrors()
            ->assertViewHas('tasks', function ($tasks) {
                // Ensure only global tasks are returned
                $todoTasks = $tasks['todo'];

                return $todoTasks->count() === 1 && $todoTasks->first()->title === 'Global Task';
            });
    }

    public function test_user_can_take_unassigned_task(): void
    {
        $task = Task::create([
            'title' => 'Unassigned Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->adminUser->id,
            'assigned_to' => null,
            'status' => 'todo',
        ]);

        Livewire::actingAs($this->workerUser)
            ->test(KanbanBoard::class)
            ->call('takeTask', $task->id)
            ->assertHasNoErrors();

        $this->assertEquals($this->workerUser->id, $task->fresh()->assigned_to);
    }

    public function test_user_can_start_and_stop_timer(): void
    {
        $task = Task::create([
            'title' => 'Timer Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->adminUser->id,
            'assigned_to' => $this->workerUser->id,
            'status' => 'todo',
        ]);

        // 1. Start timer
        Livewire::actingAs($this->workerUser)
            ->test(KanbanBoard::class)
            ->call('toggleTimer', $task->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('task_time_logs', [
            'task_id' => $task->id,
            'user_id' => $this->workerUser->id,
            'stopped_at' => null,
        ]);

        // 2. Stop timer
        Livewire::actingAs($this->workerUser)
            ->test(KanbanBoard::class)
            ->call('toggleTimer', $task->id)
            ->assertHasNoErrors();

        $log = $task->timeLogs()->first();
        $this->assertNotNull($log->stopped_at);
        $this->assertGreaterThanOrEqual(0, $log->duration_seconds);
    }

    public function test_task_time_spent_user_breakdown(): void
    {
        $task = Task::create([
            'title' => 'Breakdown Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->adminUser->id,
            'assigned_to' => $this->workerUser->id,
            'status' => 'todo',
        ]);

        // Create time logs for different users
        // User 1: workerUser, duration 100s
        $task->timeLogs()->create([
            'user_id' => $this->workerUser->id,
            'started_at' => now()->subSeconds(200),
            'stopped_at' => now()->subSeconds(100),
            'duration_seconds' => 100,
        ]);

        // User 2: adminUser, duration 50s
        $task->timeLogs()->create([
            'user_id' => $this->adminUser->id,
            'started_at' => now()->subSeconds(60),
            'stopped_at' => now()->subSeconds(10),
            'duration_seconds' => 50,
        ]);

        // Verify task human duration
        $this->assertEquals('2m 30s', $task->human_formatted_duration);

        // Verify breakdown
        $breakdown = $task->getDurationByUser();
        $this->assertCount(2, $breakdown);

        // The first user should be workerUser (100s > 50s)
        $this->assertEquals($this->workerUser->id, $breakdown[0]['user']->id);
        $this->assertEquals(100, $breakdown[0]['duration']);
        $this->assertEquals('00:01:40', $breakdown[0]['formatted']);
        $this->assertEquals('1m 40s', $breakdown[0]['human']);

        // The second user should be adminUser (50s)
        $this->assertEquals($this->adminUser->id, $breakdown[1]['user']->id);
        $this->assertEquals(50, $breakdown[1]['duration']);
        $this->assertEquals('00:00:50', $breakdown[1]['formatted']);
        $this->assertEquals('50s', $breakdown[1]['human']);
    }

    public function test_role_based_task_visibility(): void
    {
        // Create an admin task
        $adminTask = Task::create([
            'title' => 'Admin Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->adminUser->id,
            'assigned_to' => $this->adminUser->id,
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        // Create a manager task
        $managerUser = User::factory()->create();
        $managerUser->assignRole('manager');
        $managerTask = Task::create([
            'title' => 'Manager Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->adminUser->id,
            'assigned_to' => $managerUser->id,
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        // Create a worker task
        $workerTask = Task::create([
            'title' => 'Worker Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->adminUser->id,
            'assigned_to' => $this->workerUser->id,
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        // Create an unassigned task
        $unassignedTask = Task::create([
            'title' => 'Unassigned Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->adminUser->id,
            'assigned_to' => null,
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        // 1. Worker should see: own task, unassigned task. NOT other tasks.
        Livewire::actingAs($this->workerUser)
            ->test(KanbanBoard::class)
            ->assertSee('Worker Task')
            ->assertSee('Unassigned Task')
            ->assertDontSee('Admin Task')
            ->assertDontSee('Manager Task');

        // 2. Manager should see: own task, worker task, unassigned task. NOT admin task.
        Livewire::actingAs($managerUser)
            ->test(KanbanBoard::class)
            ->assertSee('Manager Task')
            ->assertSee('Worker Task')
            ->assertSee('Unassigned Task')
            ->assertDontSee('Admin Task');

        // 3. Admin should see: all tasks.
        Livewire::actingAs($this->adminUser)
            ->test(KanbanBoard::class)
            ->assertSee('Admin Task')
            ->assertSee('Manager Task')
            ->assertSee('Worker Task')
            ->assertSee('Unassigned Task');
    }
}
