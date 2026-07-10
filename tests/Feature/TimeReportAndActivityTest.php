<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Client;
use App\Models\Website;
use App\Models\Task;
use App\Models\TaskTimeLog;
use Livewire\Livewire;
use Carbon\Carbon;

class TimeReportAndActivityTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $managerUser;
    protected User $workerUser;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create users
        $this->adminUser = User::factory()->create()->assignRole('admin');
        $this->managerUser = User::factory()->create()->assignRole('manager');
        $this->workerUser = User::factory()->create()->assignRole('worker');

        // Create project
        $this->project = Project::factory()->create([
            'manager_id' => $this->adminUser->id,
        ]);
    }

    public function test_time_report_route_requires_auth_and_correct_role(): void
    {
        // Guest cannot access
        $this->get('/reports/time')->assertRedirect(route('login'));

        // Worker cannot access
        $this->actingAs($this->workerUser)->get('/reports/time')->assertForbidden();

        // Manager can access
        $this->actingAs($this->managerUser)->get('/reports/time')->assertOk();

        // Admin can access
        $this->actingAs($this->adminUser)->get('/reports/time')->assertOk();
    }

    public function test_time_report_livewire_calculations_work(): void
    {
        $this->actingAs($this->adminUser);

        $task = Task::create([
            'title' => 'Calculations Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->adminUser->id,
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        // Create time logs
        TaskTimeLog::create([
            'task_id' => $task->id,
            'user_id' => $this->workerUser->id,
            'started_at' => Carbon::now()->subMinutes(60),
            'stopped_at' => Carbon::now(),
            'duration_seconds' => 3600,
        ]);

        TaskTimeLog::create([
            'task_id' => $task->id,
            'user_id' => $this->adminUser->id,
            'started_at' => Carbon::now()->subMinutes(30),
            'stopped_at' => Carbon::now(),
            'duration_seconds' => 1800,
        ]);

        // Test Livewire component
        Livewire::test(\App\Livewire\Reports\TimeReport::class)
            ->assertSet('userId', '') // Defaults to All
            ->assertSee('1h 30m') // Total duration
            ->assertSee($this->workerUser->name)
            ->assertSee($this->adminUser->name)
            // Filter by workerUser
            ->set('userId', $this->workerUser->id)
            ->assertSee('1h')
            ->assertDontSee('1h 30m');
    }

    public function test_task_events_are_logged_to_activity_logs(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Log task_created
        $task = Task::create([
            'title' => 'Feature Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->adminUser->id,
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'task_id' => $task->id,
            'action' => 'task_created',
            'user_id' => $this->adminUser->id,
        ]);

        // 2. Log task_status_updated
        $task->status = 'in_progress';
        $task->save();

        $this->assertDatabaseHas('activity_logs', [
            'task_id' => $task->id,
            'action' => 'task_status_updated',
            'user_id' => $this->adminUser->id,
        ]);

        // 3. Log task_claimed (assign to current user)
        $task->assigned_to = $this->adminUser->id;
        $task->save();

        $this->assertDatabaseHas('activity_logs', [
            'task_id' => $task->id,
            'action' => 'task_claimed',
            'user_id' => $this->adminUser->id,
        ]);

        // 4. Log task_assigned (assign to someone else)
        $task->assigned_to = $this->workerUser->id;
        $task->save();

        $this->assertDatabaseHas('activity_logs', [
            'task_id' => $task->id,
            'action' => 'task_assigned',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_timer_events_are_logged_to_activity_logs(): void
    {
        $this->actingAs($this->adminUser);

        $task = Task::create([
            'title' => 'Timer Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->adminUser->id,
            'assigned_to' => $this->adminUser->id,
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        // Start timer
        Livewire::test(\App\Livewire\Tasks\KanbanBoard::class)
            ->call('toggleTimer', $task->id);

        $this->assertDatabaseHas('activity_logs', [
            'task_id' => $task->id,
            'action' => 'timer_started',
            'user_id' => $this->adminUser->id,
        ]);

        // Stop timer
        Livewire::test(\App\Livewire\Tasks\KanbanBoard::class)
            ->call('toggleTimer', $task->id);

        $this->assertDatabaseHas('activity_logs', [
            'task_id' => $task->id,
            'action' => 'timer_stopped',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_client_portal_task_submission_logs_activity(): void
    {
        $client = Client::create([
            'name' => 'Nexus Client',
            'hash' => 'nexushash12345678901234567890123',
        ]);

        $company = Project::factory()->create([
            'name' => 'Nexus Corp',
            'client_id' => $client->id,
        ]);

        $website = Website::create([
            'project_id' => $company->id,
            'name' => 'Nexus Site',
            'url' => 'https://nexus.com',
            'status' => 'Live',
        ]);

        Livewire::test(\App\Livewire\ClientPortal::class, ['hash' => $client->hash])
            ->set('selectedCompanyId', $company->id)
            ->set('selectedWebsiteId', $website->id)
            ->set('requestType', 'Bug Report')
            ->set('description', 'Website is completely unresponsive right now.')
            ->call('submitReport')
            ->assertHasNoErrors();

        // Assert activity log has portal submission entry
        $this->assertDatabaseHas('activity_logs', [
            'client_id' => $client->id,
            'project_id' => $company->id,
            'action' => 'client_portal_task_created',
        ]);
    }
}
