<?php

namespace App\Livewire\Projects;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Review;
use App\Models\SmmPost;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OperationsSection extends Component
{
    public Project $project;

    // Sub-tab selection: smm | reviews
    public string $activeSubTab = 'smm';

    // SMM Form properties
    public string $smmPlatform = 'LinkedIn';

    public string $smmTitle = '';

    public string $smmContent = '';

    public string $smmUrl = '';

    public string $smmStatus = 'draft';

    public ?string $smmPublishedAt = null;

    // Review Form properties
    public string $reviewPlatform = 'Trustpilot';

    public string $reviewerName = '';

    public int $reviewRating = 5;

    public string $reviewContent = '';

    public string $reviewUrl = '';

    public string $reviewStatus = 'pending';

    // Editing states
    public ?int $editingPostId = null;

    public ?int $editingReviewId = null;

    // Flash notifications
    public ?string $flashMessage = null;

    public ?string $flashType = null;

    public function mount(Project $project): void
    {
        $this->project = $project;
        if (! config('features.smm', true)) {
            $this->activeSubTab = 'reviews';
        }
    }

    public function setSubTab(string $tab): void
    {
        $this->activeSubTab = $tab;
        $this->resetForms();
    }

    private function resetForms(): void
    {
        $this->smmPlatform = 'LinkedIn';
        $this->smmTitle = '';
        $this->smmContent = '';
        $this->smmUrl = '';
        $this->smmStatus = 'draft';
        $this->smmPublishedAt = null;
        $this->editingPostId = null;

        $this->reviewPlatform = 'Trustpilot';
        $this->reviewerName = '';
        $this->reviewRating = 5;
        $this->reviewContent = '';
        $this->reviewUrl = '';
        $this->reviewStatus = 'pending';
        $this->editingReviewId = null;
    }

    public function addPost(): void
    {
        $this->validate([
            'smmTitle' => 'required|string|min:3|max:255',
            'smmPlatform' => 'required|string',
            'smmUrl' => 'nullable|url',
        ]);

        if ($this->editingPostId) {
            $post = SmmPost::findOrFail($this->editingPostId);
            $post->update([
                'platform' => $this->smmPlatform,
                'title' => $this->smmTitle,
                'content' => $this->smmContent,
                'url' => $this->smmUrl,
                'status' => $this->smmStatus,
                'published_at' => $this->smmStatus === 'published' ? ($this->smmPublishedAt ?: now()) : null,
            ]);
            $this->flash('SMM Post updated successfully.', 'success');
        } else {
            SmmPost::create([
                'project_id' => $this->project->id,
                'platform' => $this->smmPlatform,
                'title' => $this->smmTitle,
                'content' => $this->smmContent,
                'url' => $this->smmUrl,
                'status' => $this->smmStatus,
                'published_at' => $this->smmStatus === 'published' ? now() : null,
            ]);

            // Log to ActivityLog
            ActivityLog::create([
                'user_id' => Auth::id(),
                'project_id' => $this->project->id,
                'action' => 'project_updated',
                'description' => "Added SMM post '{$this->smmTitle}' to company",
            ]);

            $this->flash('SMM Post created successfully.', 'success');
        }

        $this->resetForms();
    }

    public function editPost(int $id): void
    {
        $post = SmmPost::findOrFail($id);
        $this->editingPostId = $post->id;
        $this->smmPlatform = $post->platform;
        $this->smmTitle = $post->title;
        $this->smmContent = $post->content ?? '';
        $this->smmUrl = $post->url ?? '';
        $this->smmStatus = $post->status;
        $this->smmPublishedAt = $post->published_at ? $post->published_at->format('Y-m-d\TH:i') : null;
    }

    public function deletePost(int $id): void
    {
        $post = SmmPost::findOrFail($id);
        $post->delete();
        $this->flash('SMM Post deleted.', 'success');
    }

    public function addReview(): void
    {
        $this->validate([
            'reviewerName' => 'required|string|min:2|max:255',
            'reviewPlatform' => 'required|string',
            'reviewRating' => 'required|integer|min:1|max:5',
            'reviewUrl' => 'nullable|url',
        ]);

        if ($this->editingReviewId) {
            $review = Review::findOrFail($this->editingReviewId);
            $review->update([
                'platform' => $this->reviewPlatform,
                'reviewer_name' => $this->reviewerName,
                'rating' => $this->reviewRating,
                'content' => $this->reviewContent,
                'url' => $this->reviewUrl,
                'status' => $this->reviewStatus,
            ]);
            $this->flash('Review updated successfully.', 'success');
        } else {
            Review::create([
                'project_id' => $this->project->id,
                'platform' => $this->reviewPlatform,
                'reviewer_name' => $this->reviewerName,
                'rating' => $this->reviewRating,
                'content' => $this->reviewContent,
                'url' => $this->reviewUrl,
                'status' => $this->reviewStatus,
            ]);

            // Log to ActivityLog
            ActivityLog::create([
                'user_id' => Auth::id(),
                'project_id' => $this->project->id,
                'action' => 'project_updated',
                'description' => "Added customer review from '{$this->reviewerName}' to company",
            ]);

            $this->flash('Review created successfully.', 'success');
        }

        $this->resetForms();
    }

    public function editReview(int $id): void
    {
        $review = Review::findOrFail($id);
        $this->editingReviewId = $review->id;
        $this->reviewPlatform = $review->platform;
        $this->reviewerName = $review->reviewer_name;
        $this->reviewRating = $review->rating;
        $this->reviewContent = $review->content ?? '';
        $this->reviewUrl = $review->url ?? '';
        $this->reviewStatus = $review->status;
    }

    public function deleteReview(int $id): void
    {
        $review = Review::findOrFail($id);
        $review->delete();
        $this->flash('Review deleted.', 'success');
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
        $posts = SmmPost::where('project_id', $this->project->id)->orderBy('created_at', 'desc')->get();
        $reviews = Review::where('project_id', $this->project->id)->orderBy('created_at', 'desc')->get();

        return view('livewire.projects.operations-section', compact('posts', 'reviews'));
    }
}
