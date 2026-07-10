<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->user = User::factory()->create()->assignRole('admin');
    }

    public function test_calendar_route_is_accessible_when_feature_enabled(): void
    {
        config(['features.calendar' => true]);

        // Create a task with a due date to ensure formatting works
        $project = \App\Models\Project::factory()->create();
        \App\Models\Task::create([
            'title' => 'Calendar Task',
            'project_id' => $project->id,
            'creator_id' => $this->user->id,
            'assigned_to' => $this->user->id,
            'due_date' => now()->addDays(5)->format('Y-m-d'),
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($this->user)->get('/calendar');

        $response->assertStatus(200);
    }

    public function test_calendar_route_aborts_404_when_feature_disabled(): void
    {
        config(['features.calendar' => false]);

        $response = $this->actingAs($this->user)->get('/calendar');

        $response->assertStatus(404);
    }

    public function test_settings_route_is_accessible_when_feature_enabled(): void
    {
        config(['features.settings_panel' => true]);

        $response = $this->actingAs($this->user)->get('/settings');

        $response->assertStatus(200);
    }

    public function test_settings_route_aborts_404_when_feature_disabled(): void
    {
        config(['features.settings_panel' => false]);

        $response = $this->actingAs($this->user)->get('/settings');

        $response->assertStatus(404);
    }

    public function test_clients_route_is_accessible_when_feature_enabled(): void
    {
        config(['features.clients' => true]);

        $response = $this->actingAs($this->user)->get('/clients');

        $response->assertStatus(200);
    }

    public function test_clients_route_aborts_404_when_feature_disabled(): void
    {
        config(['features.clients' => false]);

        $response = $this->actingAs($this->user)->get('/clients');

        $response->assertStatus(404);
    }

    public function test_users_route_is_accessible_when_feature_enabled(): void
    {
        config(['features.users' => true]);

        $response = $this->actingAs($this->user)->get('/users');

        $response->assertStatus(200);
    }

    public function test_users_route_aborts_404_when_feature_disabled(): void
    {
        config(['features.users' => false]);

        $response = $this->actingAs($this->user)->get('/users');

        $response->assertStatus(404);
    }

    public function test_my_work_route_is_accessible_when_feature_enabled(): void
    {
        config(['features.my_work' => true]);

        $response = $this->actingAs($this->user)->get('/my-work');

        $response->assertStatus(200);
    }

    public function test_my_work_route_aborts_404_when_feature_disabled(): void
    {
        config(['features.my_work' => false]);

        $response = $this->actingAs($this->user)->get('/my-work');

        $response->assertStatus(404);
    }

    public function test_project_details_shows_or_hides_tabs_based_on_feature_flags(): void
    {
        $project = \App\Models\Project::factory()->create();

        // 1. All enabled
        config([
            'features.credential_vault' => true,
            'features.websites_tab' => true,
            'features.compliance_tab' => true,
            'features.reports_tab' => true,
            'features.notes_tab' => true,
            'features.operations_tab' => true,
            'features.company_changelog' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('projects.show', $project));
        $response->assertSee("activeTab = 'credentials'", false)
            ->assertSee("activeTab = 'websites'", false)
            ->assertSee("activeTab = 'boarding'", false)
            ->assertSee("activeTab = 'reports'", false)
            ->assertSee("activeTab = 'notes'", false)
            ->assertSee("activeTab = 'operations'", false)
            ->assertSee("activeTab = 'changelog'", false);

        // 2. Disabled some tabs
        config([
            'features.credential_vault' => false,
            'features.websites_tab' => false,
            'features.compliance_tab' => false,
            'features.reports_tab' => false,
            'features.notes_tab' => false,
            'features.operations_tab' => false,
            'features.company_changelog' => false,
        ]);

        $response = $this->actingAs($this->user)->get(route('projects.show', $project));
        $response->assertDontSee("activeTab = 'credentials'", false)
            ->assertDontSee("activeTab = 'websites'", false)
            ->assertDontSee("activeTab = 'boarding'", false)
            ->assertDontSee("activeTab = 'reports'", false)
            ->assertDontSee("activeTab = 'notes'", false)
            ->assertDontSee("activeTab = 'operations'", false)
            ->assertDontSee("activeTab = 'changelog'", false);
    }

    public function test_kanban_board_details_shows_or_hides_subfeatures_based_on_flags(): void
    {
        $project = \App\Models\Project::factory()->create();
        $task = \App\Models\Task::create([
            'title' => 'Feature Test Task',
            'project_id' => $project->id,
            'creator_id' => $this->user->id,
            'assigned_to' => $this->user->id,
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        // 1. All enabled
        config([
            'features.task_comments' => true,
            'features.task_time_logs' => true,
            'features.task_history' => true,
            'features.task_attachments' => true,
            'features.session_log_history' => true,
        ]);

        \Livewire\Livewire::actingAs($this->user)
            ->test(\App\Livewire\Tasks\KanbanBoard::class)
            ->call('openTaskModal', $task->id)
            ->assertSee('Comments')
            ->assertSee('Work Logs')
            ->assertSee('History')
            ->assertSee('Documents and Attachments')
            ->assertSee('Work Session Log History')
            ->assertSee('Time Tracker');

        // 2. Disabled
        config([
            'features.task_comments' => false,
            'features.task_time_logs' => false,
            'features.task_history' => false,
            'features.task_attachments' => false,
            'features.session_log_history' => false,
        ]);

        \Livewire\Livewire::actingAs($this->user)
            ->test(\App\Livewire\Tasks\KanbanBoard::class)
            ->call('openTaskModal', $task->id)
            ->assertDontSee('Comments')
            ->assertDontSee('Work Logs')
            ->assertDontSee('History')
            ->assertDontSee('Documents and Attachments')
            ->assertDontSee('Work Session Log History')
            ->assertDontSee('Time Tracker');
    }

    public function test_client_portal_shows_or_hides_subfeatures_based_on_flags(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $client = \App\Models\Client::create([
            'name' => 'Flag Test Client',
            'hash' => 'flaghash12345678901234567890123456',
        ]);

        $project = \App\Models\Project::factory()->create([
            'client_id' => $client->id,
        ]);

        $task = \App\Models\Task::create([
            'title' => 'Flag Task',
            'project_id' => $project->id,
            'creator_id' => $this->user->id,
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        // Attach a file to task to test attachments section visibility
        $file = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100);
        $task->addMedia($file)->toMediaCollection('documents');

        $this->assertCount(1, $task->getMedia('documents'));

        // 1. All enabled
        config([
            'features.client_portal_comments' => true,
            'features.task_attachments' => true,
        ]);

        \Livewire\Livewire::test(\App\Livewire\ClientPortal::class, ['hash' => $client->hash])
            ->call('openTaskModal', $task->id)
            ->assertSee('Discussion')
            ->assertSee('Attachments');

        // 2. Disabled
        config([
            'features.client_portal_comments' => false,
            'features.task_attachments' => false,
        ]);

        \Livewire\Livewire::test(\App\Livewire\ClientPortal::class, ['hash' => $client->hash])
            ->call('openTaskModal', $task->id)
            ->assertDontSee('Discussion')
            ->assertDontSee('Attachments');
    }
}
