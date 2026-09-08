<?php

namespace Tests\Feature;

use App\Livewire\Tasks\KanbanBoard;
use App\Livewire\Users\Index as UsersIndex;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserAssigneeColorTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->actingAs($this->admin);
    }

    public function test_user_color_defaults_to_sky_blue(): void
    {
        $user = User::factory()->create(['color' => null]);
        $this->assertEquals('#3b82f6', $user->color);
    }

    public function test_admin_can_set_custom_color_for_user(): void
    {
        $user = User::factory()->create(['color' => '#3b82f6']);

        Livewire::test(UsersIndex::class)
            ->call('openEditModal', $user->id)
            ->set('color', '#ef4444')
            ->call('saveUser');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'color' => '#ef4444',
        ]);
    }

    public function test_kanban_board_renders_assignee_color_border(): void
    {
        $assignee = User::factory()->create([
            'name' => 'Alice Worker',
            'color' => '#10b981',
        ]);

        $task = Task::create([
            'title' => 'Color Highlighted Task',
            'assigned_to' => $assignee->id,
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        Livewire::test(KanbanBoard::class)
            ->assertSee('Color Highlighted Task')
            ->assertSee('border-left: 4px solid #10b981;', false);
    }
}
