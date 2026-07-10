<?php

namespace Tests\Feature;

use App\Livewire\Projects\CommentsSection;
use App\Livewire\Projects\OperationsSection;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Review;
use App\Models\SmmPost;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OperationsAndCommentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        config([
            'features.smm' => true,
            'features.company_comments' => true,
        ]);
    }

    public function test_can_manage_company_comments(): void
    {
        $user = User::factory()->create()->assignRole('manager');
        $this->actingAs($user);

        $project = Project::factory()->create();

        // 1. Add Comment
        Livewire::test(CommentsSection::class, ['project' => $project])
            ->set('newCommentContent', 'Hello general chat')
            ->call('addComment')
            ->assertSet('newCommentContent', '')
            ->assertSee('Hello general chat');

        $this->assertDatabaseHas('comments', [
            'project_id' => $project->id,
            'task_id' => null,
            'content' => 'Hello general chat',
        ]);

        $comment = Comment::where('project_id', $project->id)->first();

        // 2. Add Reply
        Livewire::test(CommentsSection::class, ['project' => $project])
            ->set('replyCommentContent.'.$comment->id, 'Replying to you')
            ->call('addReply', $comment->id)
            ->assertSee('Replying to you');

        $this->assertDatabaseHas('comments', [
            'project_id' => $project->id,
            'parent_id' => $comment->id,
            'content' => 'Replying to you',
        ]);

        // 3. Delete Comment
        Livewire::test(CommentsSection::class, ['project' => $project])
            ->call('deleteComment', $comment->id);

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_can_manage_operations_posts_and_reviews(): void
    {
        $user = User::factory()->create()->assignRole('manager');
        $this->actingAs($user);

        $project = Project::factory()->create();

        // 1. Add SMM Post
        Livewire::test(OperationsSection::class, ['project' => $project])
            ->set('smmPlatform', 'LinkedIn')
            ->set('smmTitle', 'New LinkedIn Post')
            ->set('smmContent', 'Post content text')
            ->set('smmStatus', 'published')
            ->call('addPost')
            ->assertSee('New LinkedIn Post');

        $this->assertDatabaseHas('smm_posts', [
            'project_id' => $project->id,
            'platform' => 'LinkedIn',
            'title' => 'New LinkedIn Post',
            'status' => 'published',
        ]);

        $post = SmmPost::first();

        // 2. Add Customer Review
        Livewire::test(OperationsSection::class, ['project' => $project])
            ->set('reviewPlatform', 'Trustpilot')
            ->set('reviewerName', 'John Reviewer')
            ->set('reviewRating', 5)
            ->set('reviewContent', 'Perfect service')
            ->call('addReview')
            ->call('setSubTab', 'reviews')
            ->assertSee('John Reviewer')
            ->assertSee('Perfect service');

        $this->assertDatabaseHas('reviews', [
            'project_id' => $project->id,
            'platform' => 'Trustpilot',
            'reviewer_name' => 'John Reviewer',
            'rating' => 5,
        ]);

        $review = Review::first();

        // 3. Delete Post and Review
        Livewire::test(OperationsSection::class, ['project' => $project])
            ->call('setSubTab', 'reviews')
            ->call('deleteReview', $review->id)
            ->call('setSubTab', 'smm')
            ->call('deletePost', $post->id);

        $this->assertDatabaseMissing('smm_posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }
}
