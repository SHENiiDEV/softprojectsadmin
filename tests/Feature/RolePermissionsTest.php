<?php

namespace Tests\Feature;

use App\Livewire\Settings;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $curator;

    protected User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(RolesAndPermissionsSeeder::class);

        // Create testing users
        $this->admin = User::factory()->create()->assignRole('admin');
        $this->manager = User::factory()->create()->assignRole('manager');
        $this->curator = User::factory()->create()->assignRole('curator');
        $this->worker = User::factory()->create()->assignRole('worker');

        // Enable all features for route checks
        config([
            'features.my_work' => true,
            'features.activity_center' => true,
            'features.calendar' => true,
            'features.time_tracking' => true,
            'features.productivity_report' => true,
        ]);
    }

    public function test_admin_has_access_to_all_gated_routes(): void
    {
        $this->actingAs($this->admin);

        $routes = [
            '/projects',
            '/tasks',
            '/users',
            '/reports/time',
            '/reports/productivity',
            '/deadlines',
            '/health-score',
            '/my-work',
            '/activity',
            '/credentials',
            '/calendar',
            '/settings',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertStatus(200);
        }
    }

    public function test_worker_access_is_appropriately_gated(): void
    {
        $this->actingAs($this->worker);

        // Allowed routes for worker role
        $allowed = [
            '/projects',
            '/tasks',
            '/deadlines',
            '/my-work',
            '/activity',
            '/calendar',
        ];

        // Forbidden routes for worker role
        $forbidden = [
            '/users',
            '/reports/time',
            '/reports/productivity',
            '/credentials',
            '/settings',
        ];

        foreach ($allowed as $route) {
            $this->get($route)->assertStatus(200);
        }

        foreach ($forbidden as $route) {
            $this->get($route)->assertStatus(403);
        }
    }

    public function test_non_admins_cannot_access_or_modify_settings(): void
    {
        $this->actingAs($this->manager)->get('/settings')->assertStatus(403);
        $this->actingAs($this->curator)->get('/settings')->assertStatus(403);
        $this->actingAs($this->worker)->get('/settings')->assertStatus(403);

        // Try to access Livewire settings component directly
        Livewire::actingAs($this->manager)
            ->test(Settings::class)
            ->assertStatus(403);
    }

    public function test_admin_can_toggle_permissions_dynamically_modifying_access(): void
    {
        // 1. Manager starts without access to user management
        $this->actingAs($this->manager)->get('/users')->assertStatus(403);

        // 2. Admin toggles permission 'manage_users' for role 'manager'
        $this->actingAs($this->admin);

        Livewire::test(Settings::class)
            ->call('togglePermission', 'manager', 'manage_users')
            ->assertHasNoErrors();

        // Reset Spatie's permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 3. Manager now has access
        $this->actingAs($this->manager->fresh())->get('/users')->assertStatus(200);

        // 4. Admin revokes the permission
        $this->actingAs($this->admin->fresh());

        Livewire::test(Settings::class)
            ->call('togglePermission', 'manager', 'manage_users')
            ->assertHasNoErrors();

        // Reset Spatie's permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 5. Manager is forbidden again
        $this->actingAs($this->manager->fresh())->get('/users')->assertStatus(403);
    }
}
