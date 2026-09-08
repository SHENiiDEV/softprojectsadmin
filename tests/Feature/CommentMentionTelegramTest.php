<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramMessageJob;
use App\Livewire\Tasks\KanbanBoard;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class CommentMentionTelegramTest extends TestCase
{
    use RefreshDatabase;

    protected User $author;

    protected User $mentionedUser;

    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->author = User::factory()->create([
            'name' => 'Mihails Admin',
        ]);
        $this->author->assignRole('admin');

        $this->mentionedUser = User::factory()->create([
            'name' => 'Gleb Dev',
            'telegram_username' => 'Gleb',
            'telegram_id' => '123456789',
        ]);
        $this->mentionedUser->assignRole('worker');

        $this->task = Task::create([
            'title' => 'Fix Payment Integration',
            'status' => 'todo',
            'priority' => 'high',
            'assigned_to' => $this->mentionedUser->id,
        ]);

        $this->actingAs($this->author);
    }

    public function test_telegram_notification_dispatched_when_comment_is_added_to_task(): void
    {
        Queue::fake();

        $comment = Comment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->author->id,
            'content' => 'Please review this task as soon as possible!',
        ]);

        \App\Services\NotificationService::sendNewCommentNotification($comment);

        Queue::assertPushed(SendTelegramMessageJob::class, function ($job) {
            return (string)$job->chatId === '123456789'
                && str_contains($job->text, 'Mihails Admin')
                && str_contains($job->text, 'Fix Payment Integration')
                && str_contains($job->text, 'Please review this task as soon as possible')
                && isset($job->replyMarkup['inline_keyboard'][0][0]['url']);
        });
    }
}
