<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\PciDssDocument;
use App\Models\Report;
use App\Models\SupportTicket;
use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        config([
            'features.deadline_center' => true,
            'features.activity_center' => true,
        ]);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_shows_overdue_counts_my_tasks_and_deadlines(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $project = Project::factory()->create(['name' => 'Acme Holdings', 'status' => 'active']);

        Task::create([
            'title' => 'Overdue filing',
            'project_id' => $project->id,
            'assigned_to' => $admin->id,
            'status' => 'todo',
            'priority' => 'high',
            'due_date' => today()->subDays(2),
        ]);

        Task::create([
            'title' => 'Soon task',
            'project_id' => $project->id,
            'status' => 'todo',
            'priority' => 'low',
            'due_date' => today()->addDays(3),
        ]);

        Report::create([
            'project_id' => $project->id,
            'accounts_due_by' => today()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk()
            ->assertSee('Overdue filing')
            ->assertSee('Soon task')
            ->assertSee('Accounts due')
            ->assertSee('Acme Holdings')
            ->assertSee('2 overdue');
    }

    public function test_done_tasks_are_not_counted_as_overdue(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $project = Project::factory()->create();

        Task::create([
            'title' => 'Finished long ago',
            'project_id' => $project->id,
            'assigned_to' => $admin->id,
            'status' => 'done',
            'priority' => 'low',
            'due_date' => today()->subDays(10),
        ]);

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Nothing overdue')
            ->assertDontSee('Finished long ago');
    }

    public function test_credential_activity_is_hidden_from_users_without_vault_access(): void
    {
        $project = Project::factory()->create();
        $admin = User::factory()->create()->assignRole('admin');
        $worker = User::factory()->create()->assignRole('worker');

        ActivityLog::create([
            'user_id' => $admin->id,
            'project_id' => $project->id,
            'action' => 'credential_password_revealed',
            'description' => 'Password for credential "Secret SMTP" was revealed',
        ]);

        $this->actingAs($worker)->get('/dashboard')->assertOk()->assertDontSee('Secret SMTP');
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Secret SMTP');
    }

    public function test_quick_actions_respect_permissions(): void
    {
        $worker = User::factory()->create()->assignRole('worker');
        $manager = User::factory()->create()->assignRole('manager');

        $this->actingAs($worker)->get('/dashboard')->assertOk()->assertDontSee('New company');
        $this->actingAs($manager)->get('/dashboard')->assertOk()->assertSee('New company');
    }

    public function test_expiring_pci_documents_are_listed_for_users_with_access(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $worker = User::factory()->create()->assignRole('worker');

        PciDssDocument::factory()->create(['title' => 'Quarterly ASV scan', 'valid_until' => today()->subDays(3)]);
        PciDssDocument::factory()->create(['title' => 'Annual AOC', 'valid_until' => today()->addDays(10)]);
        PciDssDocument::factory()->create(['title' => 'Far future SAQ', 'valid_until' => today()->addDays(200)]);

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Quarterly ASV scan')
            ->assertSee('Annual AOC')
            ->assertDontSee('Far future SAQ');

        $this->actingAs($worker)->get('/dashboard')->assertOk()->assertDontSee('Quarterly ASV scan');
    }

    public function test_open_support_tickets_are_listed(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        SupportTicket::create(['gmail_thread_id' => 't1', 'customer_email' => 'buyer@example.com', 'subject' => 'Where is my refund', 'status' => 'open']);
        SupportTicket::create(['gmail_thread_id' => 't2', 'customer_email' => 'old@example.com', 'subject' => 'Already answered', 'status' => 'answered']);

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Open email tickets')
            ->assertSee('Where is my refund')
            ->assertDontSee('Already answered');
    }

    public function test_my_time_card_shows_logged_time_when_time_logs_enabled(): void
    {
        config(['features.task_time_logs' => true]);

        $admin = User::factory()->create()->assignRole('admin');
        $project = Project::factory()->create();
        $task = Task::create(['title' => 'Timed', 'project_id' => $project->id, 'assigned_to' => $admin->id, 'status' => 'in_progress', 'priority' => 'low']);

        TaskTimeLog::create([
            'task_id' => $task->id,
            'user_id' => $admin->id,
            'started_at' => now()->subHours(2),
            'stopped_at' => now()->subHour(),
            'duration_seconds' => 5400,
        ]);

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('My time')
            ->assertSee('1:30');

        config(['features.task_time_logs' => false]);

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertDontSee('My time');
    }

    public function test_team_workload_is_visible_to_managers_only(): void
    {
        $manager = User::factory()->create()->assignRole('manager');
        $worker = User::factory()->create(['name' => 'Busy Bee'])->assignRole('worker');
        $project = Project::factory()->create();

        Task::create(['title' => 'Late one', 'project_id' => $project->id, 'assigned_to' => $worker->id, 'status' => 'todo', 'priority' => 'low', 'due_date' => today()->subDay()]);

        $this->actingAs($manager)->get('/dashboard')->assertOk()->assertSee('Team workload')->assertSee('Busy Bee');
        $this->actingAs($worker)->get('/dashboard')->assertOk()->assertDontSee('Team workload');
    }
}
