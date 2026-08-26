<?php

namespace Tests\Feature;

use App\Livewire\Users\Index;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $curator;

    protected User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(RolesAndPermissionsSeeder::class);

        // Create testing users
        $this->admin = User::factory()->create(['telegram_id' => 12345])->assignRole('admin');
        $this->curator = User::factory()->create(['telegram_id' => 54321])->assignRole('curator');
        $this->worker = User::factory()->create(['telegram_id' => null])->assignRole('worker');
    }

    /**
     * Test authorization on users route.
     */
    public function test_unauthorized_users_cannot_access_user_management(): void
    {
        // 1. Guest redirected
        $this->get('/users')->assertRedirect('/login');

        // 2. Worker gets 403
        $this->actingAs($this->worker)->get('/users')->assertStatus(403);

        // 3. Admin gets 200
        $this->actingAs($this->admin)->get('/users')->assertStatus(200);

        // 4. Curator gets 200
        $this->actingAs($this->curator)->get('/users')->assertStatus(200);
    }

    /**
     * Test users list page rendering.
     */
    public function test_authorized_users_can_see_user_list_and_search(): void
    {
        $this->actingAs($this->admin);

        $anotherWorker = User::factory()->create(['name' => 'Searchable Name'])->assignRole('worker');

        Livewire::test(Index::class)
            ->assertSee($this->admin->name)
            ->assertSee($this->curator->name)
            ->assertSee($this->worker->name)
            ->set('search', 'Searchable Name')
            ->assertSee('Searchable Name')
            ->assertDontSee($this->worker->name)
            ->set('search', '')
            ->set('filterRole', 'curator')
            ->assertSee($this->curator->name)
            ->assertDontSee($this->worker->name);
    }

    /**
     * Test user creation with role assignment.
     */
    public function test_curator_and_admin_can_create_user_with_role(): void
    {
        $this->actingAs($this->curator);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('name', 'New User')
            ->set('email', 'newuser@example.com')
            ->set('password', 'password123')
            ->set('role', 'manager')
            ->call('saveUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
        ]);

        $newUser = User::where('email', 'newuser@example.com')->first();
        $this->assertTrue($newUser->hasRole('manager'));
        $this->assertTrue(Hash::check('password123', $newUser->password));
    }

    /**
     * Test editing user details.
     */
    public function test_user_can_be_edited(): void
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create()->assignRole('worker');

        Livewire::test(Index::class)
            ->call('openEditModal', $user->id)
            ->set('name', 'Updated Name')
            ->set('role', 'curator')
            ->call('saveUser')
            ->assertHasNoErrors();

        $user = $user->fresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertTrue($user->hasRole('curator'));
    }

    /**
     * Test password resetting.
     */
    public function test_password_can_be_reset_by_moderator(): void
    {
        $this->actingAs($this->curator);

        $user = User::factory()->create(['password' => Hash::make('old_password')]);

        Livewire::test(Index::class)
            ->call('openResetModal', $user->id)
            ->set('newPassword', 'new_secure_password_999')
            ->call('resetPassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new_secure_password_999', $user->fresh()->password));
    }

    /**
     * Test user deletion rules.
     */
    public function test_only_admin_can_delete_users_and_cannot_delete_self(): void
    {
        $userToDelete = User::factory()->create()->assignRole('worker');

        // 1. Curator cannot delete users
        $this->actingAs($this->curator);
        Livewire::test(Index::class)
            ->call('deleteUser', $userToDelete->id)
            ->assertSee('Only administrators can delete users.');

        $this->assertDatabaseHas('users', ['id' => $userToDelete->id]);

        // 2. Admin can delete users
        $this->actingAs($this->admin);
        Livewire::test(Index::class)
            ->call('deleteUser', $userToDelete->id)
            ->assertSee('User successfully deleted.');

        $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);

        // 3. Admin cannot delete self
        Livewire::test(Index::class)
            ->call('deleteUser', $this->admin->id)
            ->assertSee('You cannot delete yourself.');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    /**
     * Test Telegram warning alert layout banner visibility.
     */
    public function test_telegram_warning_banner_visibility_based_on_telegram_id(): void
    {
        // 1. Admin has telegram_id -> banner should NOT be visible
        $this->actingAs($this->admin);
        $this->get('/dashboard')
            ->assertDontSee('Telegram notifications not configured');

        // 2. Worker has null telegram_id -> banner should be visible
        $this->actingAs($this->worker);
        $this->get('/dashboard')
            ->assertSee('Telegram notifications not configured')
            ->assertSee(route('profile'));
    }
}
