<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramMessageJob;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TaskEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(RolesAndPermissionsSeeder::class);

        // Create admin user
        $this->user = User::factory()->create(['telegram_username' => 'admin_tg'])->assignRole('admin');

        $this->client = Client::create([
            'name' => 'Acme Corp',
            'hash' => 'fakehash123',
        ]);

        $this->project = Project::factory()->create([
            'manager_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        config([
            'services.telegram.bot_token' => '123456:fake_bot_token',
        ]);
    }

    /**
     * Test creating a comment and verifying relations.
     */
    public function test_comment_relations_and_creation(): void
    {
        $task = Task::create([
            'title' => 'Test task',
            'project_id' => $this->project->id,
        ]);

        $comment = Comment::create([
            'task_id' => $task->id,
            'user_id' => $this->user->id,
            'content' => 'This is a root comment',
        ]);

        $this->assertCount(1, $task->comments);
        $this->assertEquals($comment->id, $task->comments->first()->id);
        $this->assertEquals($task->id, $comment->task->id);
        $this->assertEquals($this->user->id, $comment->user->id);
    }

    /**
     * Test threaded comments (parent and replies relations).
     */
    public function test_threaded_comments_replies(): void
    {
        $task = Task::create([
            'title' => 'Test task',
            'project_id' => $this->project->id,
        ]);

        $parentComment = Comment::create([
            'task_id' => $task->id,
            'user_id' => $this->user->id,
            'content' => 'Root comment',
        ]);

        $reply = Comment::create([
            'task_id' => $task->id,
            'user_id' => $this->user->id,
            'parent_id' => $parentComment->id,
            'content' => 'This is a reply',
        ]);

        // Root comments list should only contain the parent
        $this->assertCount(1, $task->comments);
        $this->assertEquals($parentComment->id, $task->comments->first()->id);

        // Parent comment replies list should contain the reply
        $this->assertCount(1, $parentComment->replies);
        $this->assertEquals($reply->id, $parentComment->replies->first()->id);
        $this->assertEquals($parentComment->id, $reply->parent->id);
    }

    /**
     * Test private comment is hidden from client-facing query.
     */
    public function test_private_comment_visibility(): void
    {
        $task = Task::create([
            'title' => 'Test task',
            'project_id' => $this->project->id,
        ]);

        $publicComment = Comment::create([
            'task_id' => $task->id,
            'user_id' => $this->user->id,
            'content' => 'Public comment',
            'is_private' => false,
        ]);

        $privateComment = Comment::create([
            'task_id' => $task->id,
            'user_id' => $this->user->id,
            'content' => 'Private comment',
            'is_private' => true,
        ]);

        $this->assertCount(2, Comment::where('task_id', $task->id)->get());

        // Client portal checks visibility:
        $clientComments = Comment::where('task_id', $task->id)->where('is_private', false)->get();
        $this->assertCount(1, $clientComments);
        $this->assertEquals($publicComment->id, $clientComments->first()->id);
    }

    /**
     * Test extracting mentions from comment text.
     */
    public function test_extracts_mentions_properly(): void
    {
        $otherUser = User::factory()->create([
            'telegram_username' => 'alex_dev',
            'telegram_id' => 98765,
        ]);

        $task = Task::create([
            'title' => 'Test task',
            'project_id' => $this->project->id,
        ]);

        $comment = Comment::create([
            'task_id' => $task->id,
            'user_id' => $this->user->id,
            'content' => 'Hey @alex_dev please check this task, also tell @unknown_user.',
        ]);

        $mentioned = $comment->getMentionedUsers();
        $this->assertCount(1, $mentioned);
        $this->assertEquals($otherUser->id, $mentioned->first()->id);
    }

    /**
     * Test comment creation triggers telegram notification.
     */
    public function test_comment_dispatches_telegram_notifications(): void
    {
        $worker = User::factory()->create([
            'telegram_id' => 999333,
            'telegram_username' => 'workertg',
        ]);

        $task = Task::create([
            'title' => 'Urgent Fix Needed',
            'project_id' => $this->project->id,
            'assigned_to' => $worker->id,
            'creator_id' => $this->user->id,
        ]);

        // Start faking queue now
        Queue::fake();

        // 1. Regular comment on task (should notify assignee $worker)
        $comment = Comment::create([
            'task_id' => $task->id,
            'user_id' => $this->user->id,
            'content' => 'Please look at this now',
        ]);

        NotificationService::sendNewCommentNotification($comment);

        Queue::assertPushed(SendTelegramMessageJob::class, function ($job) use ($worker) {
            return $job->chatId === $worker->telegram_id
                && str_contains($job->text, 'Please look at this now');
        });

        // 2. Mentioning worker in another comment (should also dispatch)
        $comment2 = Comment::create([
            'task_id' => $task->id,
            'user_id' => $this->user->id,
            'content' => 'Calling @workertg to assist',
        ]);

        NotificationService::sendNewCommentNotification($comment2);

        Queue::assertPushed(SendTelegramMessageJob::class, function ($job) use ($worker) {
            return $job->chatId === $worker->telegram_id
                && str_contains($job->text, 'Calling @workertg');
        });
    }

    /**
     * Test deadline reminder command triggers notifications correctly.
     */
    public function test_deadline_reminder_notifies_assignee_tomorrow(): void
    {
        $worker = User::factory()->create([
            'telegram_id' => 888222,
        ]);

        // Task due tomorrow
        $task = Task::create([
            'title' => 'Deadline Task',
            'project_id' => $this->project->id,
            'assigned_to' => $worker->id,
            'due_date' => Carbon::tomorrow()->toDateString(),
            'status' => 'in_progress',
            'deadline_reminder_sent' => false,
        ]);

        // Task due tomorrow but already done (should NOT notify)
        $doneTask = Task::create([
            'title' => 'Finished Task',
            'project_id' => $this->project->id,
            'assigned_to' => $worker->id,
            'due_date' => Carbon::tomorrow()->toDateString(),
            'status' => 'done',
            'deadline_reminder_sent' => false,
        ]);

        // Start faking queue now
        Queue::fake();

        Artisan::call('tasks:check-deadlines');

        // Verify notification for in_progress task is sent
        Queue::assertPushed(SendTelegramMessageJob::class, function ($job) use ($worker) {
            return $job->chatId === $worker->telegram_id
                && str_contains($job->text, 'Deadline Task')
                && str_contains($job->text, 'expires tomorrow');
        });

        // Verify doneTask did NOT trigger notification
        Queue::assertNotPushed(SendTelegramMessageJob::class, function ($job) use ($worker) {
            return $job->chatId === $worker->telegram_id
                && str_contains($job->text, 'Finished Task');
        });

        $this->assertTrue($task->fresh()->deadline_reminder_sent);
        $this->assertFalse($doneTask->fresh()->deadline_reminder_sent);
    }
}
