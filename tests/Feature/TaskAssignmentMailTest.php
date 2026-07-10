<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Mail\TaskAssignedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAssignmentMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_task_with_assignee_dispatches_task_assigned_mail(): void
    {
        Mail::fake();

        // 1. Create a user (actor) who creates the task, and an assignee
        $actor = User::factory()->create(['name' => 'John Actor']);
        $assignee = User::factory()->create(['email' => 'assignee@example.com']);

        // Acting as actor
        $this->actingAs($actor);

        // 2. Create a task assigned to the user
        $task = Task::create([
            'assigned_to' => $assignee->id,
            'creator_id' => $actor->id,
            'title' => 'Complete the deployment script',
        ]);

        // 3. Assert that email was sent to the assignee
        Mail::assertSent(TaskAssignedMail::class, function (TaskAssignedMail $mail) use ($assignee, $task, $actor) {
            return $mail->hasTo($assignee->email) &&
                   $mail->task->id === $task->id &&
                   $mail->assignee->id === $assignee->id &&
                   $mail->actor->id === $actor->id;
        });
    }

    public function test_updating_task_assignee_dispatches_task_assigned_mail(): void
    {
        Mail::fake();

        $actor = User::factory()->create(['name' => 'John Actor']);
        $assignee = User::factory()->create(['email' => 'new_assignee@example.com']);
        $this->actingAs($actor);

        // 1. Create task without assignee
        $task = Task::create([
            'assigned_to' => null,
            'creator_id' => $actor->id,
            'title' => 'Refactor auth templates',
        ]);

        // No mail sent yet
        Mail::assertNothingSent();

        // 2. Assign the task to the user
        $task->update([
            'assigned_to' => $assignee->id,
        ]);

        // 3. Assert that email was sent to new assignee
        Mail::assertSent(TaskAssignedMail::class, function (TaskAssignedMail $mail) use ($assignee, $task, $actor) {
            return $mail->hasTo($assignee->email) &&
                   $mail->task->id === $task->id &&
                   $mail->assignee->id === $assignee->id &&
                   $mail->actor->id === $actor->id;
        });
    }
}
