<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramMessageJob;
use App\Livewire\Users\Index;
use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class UserPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $curator;

    protected User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Ada Admin'])->assignRole('admin');
        $this->curator = User::factory()->create()->assignRole('curator');
        $this->worker = User::factory()->create(['name' => 'Wendy Worker', 'password' => Hash::make('old-password-1')])->assignRole('worker');
    }

    private function session(string $id, int $userId): void
    {
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => '', 'last_activity' => time()]);
    }

    public function test_admin_changes_a_password_and_the_old_one_stops_working(): void
    {
        $oldRemember = $this->worker->remember_token;

        Livewire::actingAs($this->admin)->test(Index::class)
            ->call('openResetModal', $this->worker->id)
            ->assertSet('showResetModal', true)
            ->assertSee('Wendy Worker')
            ->set('newPassword', 'brand-new-secret-77')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertSet('showResetModal', false)
            ->assertSet('newPassword', '')
            ->assertSee('Password of Wendy Worker changed');

        $worker = $this->worker->fresh();
        $this->assertTrue(Hash::check('brand-new-secret-77', $worker->password));
        $this->assertFalse(Hash::check('old-password-1', $worker->password));
        $this->assertNotSame($oldRemember, $worker->remember_token, 'remember-me cookies are invalidated');

        $log = ActivityLog::where('action', 'user_password_changed')->firstOrFail();
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertStringContainsString('Wendy Worker', $log->description);
        $this->assertStringNotContainsString('brand-new-secret-77', $log->description, 'the password is never logged');
    }

    public function test_the_user_is_told_in_the_app_and_in_telegram_without_the_password(): void
    {
        Queue::fake();
        $this->worker->update(['telegram_id' => 424242]);

        Livewire::actingAs($this->admin)->test(Index::class)
            ->call('openResetModal', $this->worker->id)
            ->set('newPassword', 'brand-new-secret-77')
            ->call('resetPassword');

        $notification = $this->worker->fresh()->notifications->first(fn ($n) => ($n->data['type'] ?? null) === 'password_changed');
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Ada Admin', $notification->data['message']);
        $this->assertStringNotContainsString('brand-new-secret-77', json_encode($notification->data));

        Queue::assertPushed(SendTelegramMessageJob::class, fn ($job) => $job->chatId === 424242 && ! str_contains($job->text, 'brand-new-secret-77'));
    }

    public function test_sessions_of_the_user_are_ended_unless_the_box_is_unticked(): void
    {
        config(['session.driver' => 'database']);
        $this->session('worker-phone', $this->worker->id);
        $this->session('worker-laptop', $this->worker->id);
        $this->session('someone-else', $this->admin->id);

        $component = Livewire::actingAs($this->admin)->test(Index::class)
            ->call('openResetModal', $this->worker->id)
            ->assertSet('signOutEverywhere', true)
            ->set('newPassword', 'brand-new-secret-77')
            ->call('resetPassword')
            ->assertSee('they were signed out of all devices');

        $this->assertSame(0, DB::table('sessions')->where('user_id', $this->worker->id)->count());
        $this->assertSame(1, DB::table('sessions')->where('user_id', $this->admin->id)->count());

        $this->session('worker-tablet', $this->worker->id);

        $component->call('openResetModal', $this->worker->id)
            ->set('signOutEverywhere', false)
            ->set('newPassword', 'another-secret-88')
            ->call('resetPassword');

        $this->assertSame(1, DB::table('sessions')->where('user_id', $this->worker->id)->count());
    }

    public function test_changing_your_own_password_keeps_the_current_session_and_sends_no_notice(): void
    {
        config(['session.driver' => 'database']);
        $this->session('other-device', $this->admin->id);

        Livewire::actingAs($this->admin)->test(Index::class)
            ->call('openResetModal', $this->admin->id)
            ->set('newPassword', 'my-own-new-secret-1')
            ->call('resetPassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('my-own-new-secret-1', $this->admin->fresh()->password));
        $this->assertSame(0, DB::table('sessions')->where('id', 'other-device')->count(), 'other devices are signed out');
        $this->assertCount(0, $this->admin->fresh()->notifications, 'no notice to yourself');
    }

    public function test_a_curator_cannot_change_the_password_of_an_administrator(): void
    {
        $adminHash = $this->admin->password;

        $component = Livewire::actingAs($this->curator)->test(Index::class)
            ->call('openResetModal', $this->admin->id)
            ->assertSet('showResetModal', false)
            ->assertSee('Only administrators can change the password of an administrator.');

        // calling the action directly with a forged target does not get around it
        $component->set('editingUserId', $this->admin->id)
            ->set('newPassword', 'takeover-password-1')
            ->call('resetPassword')
            ->assertSee('Only administrators can change the password of an administrator.');

        $this->assertSame($adminHash, $this->admin->fresh()->password);
        $this->assertSame(0, ActivityLog::where('action', 'user_password_changed')->count());
    }

    public function test_a_curator_can_change_the_password_of_other_users_and_admins_can_change_admin_passwords(): void
    {
        Livewire::actingAs($this->curator)->test(Index::class)
            ->call('openResetModal', $this->worker->id)
            ->set('newPassword', 'curator-set-secret-5')
            ->call('resetPassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('curator-set-secret-5', $this->worker->fresh()->password));

        $otherAdmin = User::factory()->create()->assignRole('admin');

        Livewire::actingAs($this->admin)->test(Index::class)
            ->call('openResetModal', $otherAdmin->id)
            ->set('newPassword', 'admin-set-secret-9')
            ->call('resetPassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('admin-set-secret-9', $otherAdmin->fresh()->password));
    }

    public function test_people_without_user_management_cannot_open_the_page_or_the_action(): void
    {
        $this->actingAs($this->worker)->get('/users')->assertForbidden();

        Livewire::actingAs($this->worker)->test(Index::class)->assertForbidden();
    }

    public function test_the_password_must_be_valid(): void
    {
        $component = Livewire::actingAs($this->admin)->test(Index::class)->call('openResetModal', $this->worker->id);

        $component->set('newPassword', '')->call('resetPassword')->assertHasErrors(['newPassword' => 'required']);
        $component->set('newPassword', 'short')->call('resetPassword')->assertHasErrors(['newPassword' => 'min']);
        $component->set('newPassword', str_repeat('x', 300))->call('resetPassword')->assertHasErrors(['newPassword' => 'max']);

        $this->assertTrue(Hash::check('old-password-1', $this->worker->fresh()->password));
    }

    public function test_a_random_password_can_be_generated_and_shown(): void
    {
        $component = Livewire::actingAs($this->admin)->test(Index::class)
            ->call('openResetModal', $this->worker->id)
            ->assertSet('revealPassword', false)
            ->call('generatePassword')
            ->assertSet('revealPassword', true);

        $password = $component->get('newPassword');
        $this->assertSame(14, strlen($password));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $password);

        $component->call('resetPassword')->assertHasNoErrors();
        $this->assertTrue(Hash::check($password, $this->worker->fresh()->password));

        $this->assertNotSame($password, Livewire::actingAs($this->admin)->test(Index::class)->call('generatePassword')->get('newPassword'));
    }

    public function test_the_page_has_a_visible_password_button_and_the_edit_form_links_to_it(): void
    {
        Livewire::actingAs($this->admin)->test(Index::class)
            ->assertSee('Password')
            ->assertSee('Change password of Wendy Worker')
            ->call('openEditModal', $this->worker->id)
            ->assertSee('Set a new password for this user.')
            ->call('openResetModal', $this->worker->id)
            ->assertSet('showModal', false)
            ->assertSet('showResetModal', true)
            ->assertSee('Generate')
            ->assertSee('Sign the user out of all devices')
            ->call('closeResetModal')
            ->assertSet('showResetModal', false);
    }
}
