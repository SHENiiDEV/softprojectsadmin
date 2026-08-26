<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramMessageJob;
use App\Models\Project;
use App\Models\Report;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class TelegramNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(RolesAndPermissionsSeeder::class);

        // Create manager/admin user
        $this->user = User::factory()->create()->assignRole('admin');

        $this->project = Project::factory()->create([
            'manager_id' => $this->user->id,
        ]);

        // Define fallback credentials
        config([
            'services.telegram.bot_token' => '123456:fake_bot_token',
            'services.telegram.bot_username' => 'test_pm_compliance_bot',
        ]);
    }

    /**
     * Test token generation in profile component.
     */
    public function test_telegram_link_component_generates_token_and_displays_status(): void
    {
        $this->actingAs($this->user);

        $this->assertNull($this->user->tg_link_token);

        Livewire::test('profile.telegram-link')
            ->assertSet('telegramId', null)
            ->assertSet('telegramUsername', null);

        $this->assertNotNull($this->user->fresh()->tg_link_token);
    }

    /**
     * Test account binding via telegram:poll Artisan command.
     */
    public function test_can_link_telegram_account_via_polling_command(): void
    {
        $user = User::factory()->create([
            'tg_link_token' => 'my_secret_token_123',
            'telegram_id' => null,
        ]);

        Http::fake([
            'https://api.telegram.org/bot*/getUpdates*' => Http::response([
                'ok' => true,
                'result' => [
                    [
                        'update_id' => 8888,
                        'message' => [
                            'message_id' => 12,
                            'from' => [
                                'id' => 999111,
                                'username' => 'john_doe_telegram',
                                'is_bot' => false,
                                'first_name' => 'John',
                            ],
                            'chat' => [
                                'id' => 999111,
                                'type' => 'private',
                            ],
                            'text' => '/start my_secret_token_123',
                        ],
                    ],
                ],
            ]),
            'https://api.telegram.org/bot*/sendMessage*' => Http::response(['ok' => true]),
        ]);

        $this->assertNull($user->telegram_id);

        Artisan::call('telegram:poll', ['--once' => true]);

        $user = $user->fresh();
        $this->assertEquals(999111, $user->telegram_id);
        $this->assertEquals('john_doe_telegram', $user->telegram_username);
    }

    /**
     * Test webhook route processes updates and binds account.
     */
    public function test_can_link_telegram_account_via_webhook_route(): void
    {
        $user = User::factory()->create([
            'tg_link_token' => 'my_webhook_token_999',
            'telegram_id' => null,
        ]);

        Http::fake([
            'https://api.telegram.org/bot*/sendMessage*' => Http::response(['ok' => true]),
        ]);

        $payload = [
            'update_id' => 9999,
            'message' => [
                'message_id' => 15,
                'from' => [
                    'id' => 777222,
                    'username' => 'webhook_user_tg',
                    'is_bot' => false,
                    'first_name' => 'Webhook',
                ],
                'chat' => [
                    'id' => 777222,
                    'type' => 'private',
                ],
                'text' => '/start my_webhook_token_999',
            ],
        ];

        $response = $this->postJson('/telegram/webhook', $payload);
        $response->assertStatus(200);

        $user = $user->fresh();
        $this->assertEquals(777222, $user->telegram_id);
        $this->assertEquals('webhook_user_tg', $user->telegram_username);
    }

    /**
     * Test task assignment triggers instant notification job.
     */
    public function test_task_assignment_dispatches_telegram_message_job(): void
    {
        Queue::fake();

        $worker = User::factory()->create([
            'telegram_id' => 555444,
        ]);

        // 1. Create a task assigned to the worker
        $task = Task::create([
            'creator_id' => $this->user->id,
            'assigned_to' => $worker->id,
            'project_id' => $this->project->id,
            'title' => 'Important Compliance Task',
            'status' => 'todo',
            'priority' => 'high',
        ]);

        Queue::assertPushed(SendTelegramMessageJob::class, function ($job) use ($worker) {
            return $job->chatId === $worker->telegram_id
                && str_contains($job->text, 'Important Compliance Task')
                && str_contains($job->text, 'A new task has been assigned to you:');
        });

        // 2. Re-assign task to another worker
        $anotherWorker = User::factory()->create([
            'telegram_id' => 222333,
        ]);

        $task->update([
            'assigned_to' => $anotherWorker->id,
        ]);

        // Verify notification is sent to the new assignee
        Queue::assertPushed(SendTelegramMessageJob::class, function ($job) use ($anotherWorker) {
            return $job->chatId === $anotherWorker->telegram_id
                && str_contains($job->text, 'A task has been assigned to you:');
        });

        // Verify unassign/removal notification is sent to the old assignee
        Queue::assertPushed(SendTelegramMessageJob::class, function ($job) use ($worker) {
            return $job->chatId === $worker->telegram_id
                && str_contains($job->text, 'Task has been unassigned from you:');
        });
    }

    /**
     * Test report deadlines checking notifies manager, admins, and curators 1 day before.
     */
    public function test_report_deadline_checker_notifies_recipients_one_day_before(): void
    {
        Queue::fake();

        // Manager of the project
        $manager = User::factory()->create([
            'telegram_id' => 10101,
        ]);
        $manager->assignRole('manager');

        $project = Project::factory()->create([
            'manager_id' => $manager->id,
            'name' => 'Target Company',
        ]);

        // Create curator with telegram
        $curator = User::factory()->create([
            'telegram_id' => 20202,
        ]);
        $curator->assignRole('curator');

        // Create admin with telegram
        $admin = User::factory()->create([
            'telegram_id' => 30303,
        ]);
        $admin->assignRole('admin');

        // Report due tomorrow (exactly 1 day left)
        $tomorrow = Carbon::tomorrow()->toDateString();
        Report::create([
            'project_id' => $project->id,
            'reg_number' => 'REG123',
            'auth_code' => 'AUTH456',
            'accounts_due_by' => $tomorrow, // Tomorrow
            'statements_due_by' => Carbon::tomorrow()->addDays(30)->toDateString(), // Far away
        ]);

        // Run checker
        Artisan::call('reports:check-deadlines');

        // Verify Manager gets notified
        Queue::assertPushed(SendTelegramMessageJob::class, function ($job) use ($manager) {
            return $job->chatId === $manager->telegram_id
                && str_contains($job->text, 'Target Company')
                && str_contains($job->text, 'Financial Statement');
        });

        // Verify Curator gets notified
        Queue::assertPushed(SendTelegramMessageJob::class, function ($job) use ($curator) {
            return $job->chatId === $curator->telegram_id;
        });

        // Verify Admin gets notified
        Queue::assertPushed(SendTelegramMessageJob::class, function ($job) use ($admin) {
            return $job->chatId === $admin->telegram_id;
        });
    }
}
