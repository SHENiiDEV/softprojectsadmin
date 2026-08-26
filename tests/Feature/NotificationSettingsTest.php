<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramMessageJob;
use App\Livewire\NotificationTray;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create([
            'telegram_id' => '123456789',
            'telegram_username' => 'test_user',
        ])->assignRole('admin');

        $this->otherUser = User::factory()->create([
            'telegram_id' => '987654321',
            'telegram_username' => 'other_user',
        ])->assignRole('worker');

        $this->project = Project::factory()->create([
            'manager_id' => $this->user->id,
        ]);
    }

    public function test_can_update_telegram_notification_preferences(): void
    {
        $this->actingAs($this->user);

        // Verify initial defaults
        $this->assertTrue($this->user->getNotificationSetting('tg_notify_task_assigned', true));
        $this->assertTrue($this->user->getNotificationSetting('tg_notify_task_status_updated', true));
        $this->assertFalse($this->user->getNotificationSetting('tg_notify_task_created', false));
        $this->assertFalse($this->user->getNotificationSetting('tg_notify_timer_action', false));

        // Update preferences using Volt Component
        Volt::test('profile.telegram-link')
            ->set('tgNotifyTaskAssigned', false)
            ->set('tgNotifyTaskStatusUpdated', false)
            ->set('tgNotifyTaskCreated', true)
            ->set('tgNotifyTimerAction', true)
            ->call('saveSettings')
            ->assertHasNoErrors()
            ->assertStatus(200);

        $this->user->refresh();

        // Verify changes are saved to user database record
        $this->assertFalse($this->user->getNotificationSetting('tg_notify_task_assigned', true));
        $this->assertFalse($this->user->getNotificationSetting('tg_notify_task_status_updated', true));
        $this->assertTrue($this->user->getNotificationSetting('tg_notify_task_created', false));
        $this->assertTrue($this->user->getNotificationSetting('tg_notify_timer_action', false));
    }

    public function test_telegram_message_dispatched_respecting_user_settings(): void
    {
        $this->actingAs($this->user);

        $task = Task::create([
            'title' => 'Important Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
            'assigned_to' => $this->otherUser->id,
            'status' => 'todo',
            'priority' => 'high',
        ]);

        // Disable status update telegram notifications for $otherUser
        $this->otherUser->notification_settings = [
            'tg_notify_task_status_updated' => false,
        ];
        $this->otherUser->save();

        Queue::fake();

        // Triggers task status updated event listener
        $task->status = 'in_progress';
        $task->save();

        // Since tg_notify_task_status_updated is false, no job should be dispatched
        Queue::assertNotPushed(SendTelegramMessageJob::class);

        // Turn on status update telegram notifications for $otherUser
        $this->otherUser->notification_settings = [
            'tg_notify_task_status_updated' => true,
        ];
        $this->otherUser->save();

        $task->status = 'review';
        $task->save();

        // Job should now be dispatched
        Queue::assertPushed(SendTelegramMessageJob::class);
    }

    public function test_in_app_notifications_are_created_and_managed_via_tray(): void
    {
        $this->actingAs($this->otherUser);

        $task = Task::create([
            'title' => 'Assigned Task',
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
            'assigned_to' => $this->otherUser->id,
            'status' => 'todo',
            'priority' => 'high',
        ]);

        // Verify notification exists in database for otherUser
        $this->assertCount(1, $this->otherUser->unreadNotifications);
        $notification = $this->otherUser->unreadNotifications->first();
        $this->assertEquals('New Task Assigned', $notification->data['title']);

        // Livewire NotificationTray interaction
        Livewire::test(NotificationTray::class)
            ->assertSee('New Task Assigned')
            ->assertViewHas('unreadCount', 1)
            ->call('markAsRead', $notification->id)
            ->assertViewHas('unreadCount', 0);

        $this->otherUser->refresh();
        $this->assertCount(0, $this->otherUser->unreadNotifications);
    }

    public function test_notification_tray_mark_all_as_read(): void
    {
        $this->actingAs($this->otherUser);

        // Assign first task
        $task1 = Task::create([
            'title' => 'Assigned Task 1',
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
            'assigned_to' => $this->otherUser->id,
            'status' => 'todo',
            'priority' => 'high',
        ]);

        // Assign second task
        $task2 = Task::create([
            'title' => 'Assigned Task 2',
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
            'assigned_to' => $this->otherUser->id,
            'status' => 'todo',
            'priority' => 'high',
        ]);

        $this->assertCount(2, $this->otherUser->unreadNotifications);

        Livewire::test(NotificationTray::class)
            ->assertViewHas('unreadCount', 2)
            ->call('markAllAsRead')
            ->assertViewHas('unreadCount', 0);

        $this->otherUser->refresh();
        $this->assertCount(0, $this->otherUser->unreadNotifications);
    }
}
