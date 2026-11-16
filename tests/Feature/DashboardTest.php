<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Report;
use App\Models\Task;
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
}
