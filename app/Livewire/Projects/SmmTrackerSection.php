<?php

namespace App\Livewire\Projects;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\SmmPost;
use App\Models\User;
use App\Models\Website;
use Livewire\Component;

/**
 * SMM Tracker: one checklist row per website and social network (networks come from the website's Social Networks).
 * "Post made" and "Checked" are ticked once, each remembers who did it and when.
 */
class SmmTrackerSection extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        abort_unless($this->canView(), 403);

        $this->project = $project;
    }

    private function canView(): bool
    {
        return auth()->user()?->can('view_projects') ?? false;
    }

    private function canEdit(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->can('manage_projects') || $user->can('manage_tasks'));
    }

    public function togglePost(int $websiteId, string $network): void
    {
        $this->toggle($websiteId, $network, 'post');
    }

    public function toggleChecked(int $websiteId, string $network): void
    {
        $this->toggle($websiteId, $network, 'checked');
    }

    /**
     * @param  'post'|'checked'  $field
     */
    private function toggle(int $websiteId, string $network, string $field): void
    {
        abort_unless($this->canEdit(), 403);

        $website = $this->project->websites()->findOrFail($websiteId);
        $socials = (array) $website->socials;
        $index = collect($socials)->search(fn ($profile) => strcasecmp((string) ($profile['network'] ?? ''), $network) === 0);

        if ($index === false) {
            return;
        }

        $profile = $socials[$index];

        // Nothing to post on a profile that does not exist yet, and nothing to check before the post is made
        if (($profile['status'] ?? 'not_created') !== 'created') {
            session()->flash('error', "Create the {$profile['network']} profile first (Websites tab).");

            return;
        }

        $now = now()->toDateTimeString();
        $userId = auth()->id();

        if ($field === 'post') {
            if (! empty($profile['post_done'])) {
                // Taking the post back also withdraws the check
                $profile = array_diff_key($profile, array_flip(['post_done', 'post_done_at', 'post_done_by', 'checked', 'checked_at', 'checked_by']));
                $done = false;
            } else {
                $profile = array_merge($profile, ['post_done' => true, 'post_done_at' => $now, 'post_done_by' => $userId]);
                $done = true;
            }
            $action = $done ? 'marked as posted' : 'unmarked (post taken back)';
        } else {
            if (empty($profile['post_done'])) {
                session()->flash('error', "Mark the {$profile['network']} post as made before checking it.");

                return;
            }

            if (! empty($profile['checked'])) {
                $profile = array_diff_key($profile, array_flip(['checked', 'checked_at', 'checked_by']));
                $done = false;
            } else {
                $profile = array_merge($profile, ['checked' => true, 'checked_at' => $now, 'checked_by' => $userId]);
                $done = true;
            }
            $action = $done ? 'marked as checked' : 'check removed';
        }

        $socials[$index] = $profile;
        $website->update(['socials' => array_values($socials)]);

        ActivityLog::create([
            'user_id' => $userId,
            'project_id' => $this->project->id,
            'action' => $field === 'post' ? 'smm_post_updated' : 'smm_check_updated',
            'description' => "SMM: {$profile['network']} on {$website->name} {$action} by ".auth()->user()->name,
        ]);
    }

    public function render()
    {
        $websites = $this->project->websites()->orderBy('id')->get();

        $rows = [];
        $userIds = [];
        $totals = ['profiles' => 0, 'posted' => 0, 'checked' => 0];

        foreach ($websites as $website) {
            $profiles = $website->socialProfiles();

            foreach ($profiles as $profile) {
                $userIds[] = $profile['post_done_by'];
                $userIds[] = $profile['checked_by'];

                if ($profile['status'] === 'created') {
                    $totals['profiles']++;
                    $totals['posted'] += $profile['post_done'] ? 1 : 0;
                    $totals['checked'] += $profile['checked'] ? 1 : 0;
                }
            }

            $rows[] = ['website' => $website, 'profiles' => $profiles];
        }

        return view('livewire.projects.smm-tracker-section', [
            'rows' => $rows,
            'totals' => $totals,
            'users' => User::whereIn('id', array_filter($userIds))->pluck('name', 'id'),
            'canEdit' => $this->canEdit(),
            'legacyPosts' => SmmPost::where('project_id', $this->project->id)->count(),
        ]);
    }
}
