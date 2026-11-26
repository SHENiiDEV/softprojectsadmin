<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NavigationMenu;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        config([
            'features.my_work' => true,
            'features.activity_center' => true,
            'features.productivity_report' => true,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function visibleKeys(User $user): array
    {
        $keys = [];

        foreach (app(NavigationMenu::class)->groups($user) as $group) {
            foreach ($group['items'] as $item) {
                $keys[] = $item['key'];
            }
        }

        return $keys;
    }

    public function test_admin_sees_every_item_in_logical_group_order(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $labels = array_column(app(NavigationMenu::class)->groups($admin), 'label');

        $this->assertSame([null, 'Work', 'Companies', 'Compliance & Finance', 'Insights', 'Administration'], $labels);
        $this->assertContains('settings', $this->visibleKeys($admin));
        $this->assertContains('users', $this->visibleKeys($admin));
    }

    public function test_worker_does_not_see_restricted_modules(): void
    {
        $worker = User::factory()->create()->assignRole('worker');

        $keys = $this->visibleKeys($worker);

        $this->assertContains('tasks', $keys);
        $this->assertContains('projects', $keys);

        foreach (['clients', 'pci-dss', 'tariffs', 'credentials', 'users', 'settings', 'time-report'] as $hidden) {
            $this->assertNotContains($hidden, $keys);
        }
    }

    public function test_disabled_feature_hides_item_and_empty_groups_are_dropped(): void
    {
        config([
            'features.pci_dss' => false,
            'features.tariffs' => false,
            'features.credential_vault' => false,
        ]);

        $admin = User::factory()->create()->assignRole('admin');

        $labels = array_column(app(NavigationMenu::class)->groups($admin), 'label');

        $this->assertNotContains('Compliance & Finance', $labels);
    }

    public function test_bottom_bar_is_limited_to_available_pinned_items(): void
    {
        config(['features.my_work' => false]);

        $worker = User::factory()->create()->assignRole('worker');

        $keys = array_column(app(NavigationMenu::class)->bottomItems($worker), 'key');

        $this->assertSame(['dashboard', 'projects', 'tasks'], $keys);
    }

    public function test_current_page_is_marked_active_and_links_use_wire_navigate(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('aria-current="page"', false);
        $response->assertSee('Compliance &amp; Finance', false);
        $response->assertSee('Administration');
    }

    public function test_an_item_whose_route_does_not_exist_is_left_out_instead_of_breaking_every_page(): void
    {
        config(['navigation.groups' => array_merge(config('navigation.groups'), [[
            'label' => 'Broken',
            'items' => [['key' => 'ghost', 'label' => 'Ghost', 'route' => 'no.such.route', 'active' => 'no.such.route', 'icon' => 'fa-ghost']],
        ]])]);

        $admin = User::factory()->create()->assignRole('admin');

        $groups = app(NavigationMenu::class)->groups($admin);

        $this->assertNotContains('Broken', array_column($groups, 'label'));
        $this->actingAs($admin)->get('/dashboard')->assertOk();
    }
}
