<?php

namespace App\Livewire\Projects;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CommentsSection extends Component
{
    public Project $project;

    public string $newCommentContent = '';

    public bool $newCommentIsPrivate = false;

    public array $replyCommentContent = [];

    public ?string $flashMessage = null;

    public ?string $flashType = null;

    protected function rules(): array
    {
        return [
            'newCommentContent' => 'required|string|min:1|max:5000',
        ];
    }

    public function addComment(): void
    {
        $this->validate();

        $comment = Comment::create([
            'project_id' => $this->project->id,
            'user_id' => Auth::id(),
            'content' => $this->newCommentContent,
            'is_private' => $this->newCommentIsPrivate,
        ]);

        // Log to ActivityLog
        ActivityLog::create([
            'user_id' => Auth::id(),
            'project_id' => $this->project->id,
            'action' => 'project_updated',
            'description' => 'Comment was added to company by '.Auth::user()->name,
        ]);

        // Send notifications
        NotificationService::sendNewCommentNotification($comment);

        $this->newCommentContent = '';
        $this->newCommentIsPrivate = false;
        $this->flash('Comment added.', 'success');
    }

    public function addReply(int $parentId): void
    {
        $content = $this->replyCommentContent[$parentId] ?? '';
        if (empty(trim($content))) {
            return;
        }

        $parent = Comment::findOrFail($parentId);

        $comment = Comment::create([
            'project_id' => $this->project->id,
            'user_id' => Auth::id(),
            'parent_id' => $parentId,
            'content' => $content,
            'is_private' => $parent->is_private, // inherits privacy
        ]);

        // Send notifications
        NotificationService::sendNewCommentNotification($comment);

        unset($this->replyCommentContent[$parentId]);
        $this->flash('Reply added.', 'success');
    }

    public function deleteComment(int $commentId): void
    {
        $comment = Comment::findOrFail($commentId);
        $user = Auth::user();

        if ($user->hasAnyRole(['admin', 'manager']) || $comment->user_id === $user->id) {
            $comment->delete();
            $this->flash('Comment deleted.', 'success');
        } else {
            $this->flash('You are not authorized to delete this comment.', 'error');
        }
    }

    public function dismissFlash(): void
    {
        $this->flashMessage = null;
        $this->flashType = null;
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->flashMessage = $message;
        $this->flashType = $type;
    }

    public function render()
    {
        // Load root comments with replies and user relation
        $comments = Comment::where('project_id', $this->project->id)
            ->whereNull('task_id')
            ->whereNull('parent_id')
            ->with(['user', 'client', 'replies.user', 'replies.client'])
            ->orderBy('created_at', 'desc')
            ->get();

        $users = \App\Models\User::orderBy('name')->get();
        $clients = \App\Models\Client::orderBy('name')->get();

        return view('livewire.projects.comments-section', compact('comments', 'users', 'clients'));
    }
}
