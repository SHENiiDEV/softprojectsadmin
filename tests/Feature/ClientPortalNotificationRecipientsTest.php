<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\NotificationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPortalNotificationRecipientsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function portalNotifications(User $user): int
    {
        return $user->fresh()->notifications
            ->filter(fn ($notification) => ($notification->data['type'] ?? null) === 'client_portal_task')
            ->count();
    }

    private function makeTask(User $creator, Project $project): Task
    {
        return Task::create([
            'title' => 'Client request',
            'creator_id' => $creator->id,
            'project_id' => $project->id,
            'status' => 'todo',
            'priority' => 'medium',
        ]);
    }

    public function test_only_the_company_manager_is_notified_when_one_is_assigned(): void
    {
        $manager = User::factory()->create()->assignRole('manager');
        $admin = User::factory()->create()->assignRole('admin');
        $worker = User::factory()->create()->assignRole('worker');
        $project = Project::factory()->create(['manager_id' => $manager->id]);
        $client = Client::factory()->create();

        $task = $this->makeTask($admin, $project);
        NotificationService::sendClientPortalTaskCreated($task, $client, $project);

        $this->assertSame(1, $this->portalNotifications($manager));
        $this->assertSame(0, $this->portalNotifications($admin));
        $this->assertSame(0, $worker->notifications()->count());
    }

    public function test_admins_and_managers_but_not_workers_are_notified_without_company_manager(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $manager = User::factory()->create()->assignRole('manager');
        $worker = User::factory()->create()->assignRole('worker');
        $project = Project::factory()->create(['manager_id' => null]);
        $client = Client::factory()->create();

        $task = $this->makeTask($admin, $project);

        NotificationService::sendClientPortalTaskCreated($task, $client, $project);

        $this->assertSame(1, $this->portalNotifications($admin));
        $this->assertSame(1, $this->portalNotifications($manager));
        $this->assertSame(0, $worker->notifications()->count());
    }
}
