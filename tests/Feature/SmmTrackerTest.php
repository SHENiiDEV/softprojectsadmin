<?php

namespace Tests\Feature;

use App\Livewire\Projects\OperationsSection;
use App\Livewire\Projects\SmmTrackerSection;
use App\Livewire\Projects\WebsitesSection;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use App\Models\Website;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SmmTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected User $worker;

    protected Project $project;

    protected Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        config(['features.smm' => true, 'features.operations_tab' => true]);

        $this->worker = User::factory()->create(['name' => 'Gleb Poster'])->assignRole('worker');
        $this->project = Project::factory()->create();
        $this->website = Website::create([
            'project_id' => $this->project->id,
            'name' => 'Shop',
            'url' => 'https://shop.example.com',
            'socials' => [
                ['network' => 'Instagram', 'url' => 'https://instagram.com/shop', 'status' => 'created'],
                ['network' => 'Twitter/X', 'url' => null, 'status' => 'created'],
                ['network' => 'TikTok', 'url' => null, 'status' => 'not_created'],
            ],
        ]);
    }

    private function tracker(?User $user = null)
    {
        return Livewire::actingAs($user ?? $this->worker)->test(SmmTrackerSection::class, ['project' => $this->project]);
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(string $network): array
    {
        return collect($this->website->fresh()->socialProfiles())->firstWhere('network', $network);
    }

    public function test_every_website_and_network_gets_a_row_with_totals(): void
    {
        $this->tracker()
            ->assertSee('Shop')
            ->assertSee('Instagram')
            ->assertSee('Twitter/X')
            ->assertSee('TikTok')
            ->assertSee('Not created yet')
            ->assertSee('Create the profile first')
            ->assertSee('Posts 0/2 · Checked 0/2')
            ->assertViewHas('totals', ['profiles' => 2, 'posted' => 0, 'checked' => 0]);
    }

    public function test_post_made_is_stamped_with_who_and_when_and_logged(): void
    {
        $this->tracker()
            ->call('togglePost', $this->website->id, 'Instagram')
            ->assertSee('Gleb Poster')
            ->assertSee(today()->format('d.m.Y'))
            ->assertSee('Posts 1/2 · Checked 0/2');

        $profile = $this->profile('Instagram');
        $this->assertTrue($profile['post_done']);
        $this->assertSame($this->worker->id, $profile['post_done_by']);
        $this->assertSame(today()->toDateString(), substr($profile['post_done_at'], 0, 10));
        $this->assertSame('https://instagram.com/shop', $profile['url'], 'link and status are untouched');

        $this->assertTrue(ActivityLog::where('project_id', $this->project->id)->where('action', 'smm_post_updated')->where('description', 'like', '%Instagram on Shop marked as posted by Gleb Poster%')->exists());
    }

    public function test_a_post_must_be_made_before_it_can_be_checked_and_taking_it_back_withdraws_the_check(): void
    {
        $reviewer = User::factory()->create(['name' => 'Mila Reviewer'])->assignRole('manager');

        $component = $this->tracker($reviewer);

        $component->call('toggleChecked', $this->website->id, 'Instagram')
            ->assertSee('Mark the Instagram post as made before checking it.');
        $this->assertFalse($this->profile('Instagram')['checked']);

        $component->call('togglePost', $this->website->id, 'Instagram')
            ->call('toggleChecked', $this->website->id, 'Instagram')
            ->assertSee('Mila Reviewer')
            ->assertSee('Posts 1/2 · Checked 1/2');

        $profile = $this->profile('Instagram');
        $this->assertTrue($profile['checked']);
        $this->assertSame($reviewer->id, $profile['checked_by']);

        // un-checking keeps the post, taking the post back clears both
        $component->call('toggleChecked', $this->website->id, 'Instagram');
        $this->assertTrue($this->profile('Instagram')['post_done']);
        $this->assertFalse($this->profile('Instagram')['checked']);

        $component->call('toggleChecked', $this->website->id, 'Instagram')->call('togglePost', $this->website->id, 'Instagram');
        $profile = $this->profile('Instagram');
        $this->assertFalse($profile['post_done']);
        $this->assertFalse($profile['checked']);
        $this->assertNull($profile['checked_by']);
    }

    public function test_profiles_that_are_not_created_cannot_be_ticked(): void
    {
        $this->tracker()
            ->call('togglePost', $this->website->id, 'TikTok')
            ->assertSee('Create the TikTok profile first');

        $this->assertFalse($this->profile('TikTok')['post_done']);

        // unknown network: nothing happens
        $this->tracker()->call('togglePost', $this->website->id, 'Myspace');
        $this->assertSame(0, ActivityLog::where('action', 'smm_post_updated')->count());
    }

    public function test_viewers_without_edit_rights_see_disabled_checkboxes_and_cannot_toggle(): void
    {
        Permission::findOrCreate('view_projects', 'web');
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('view_projects');

        $this->tracker($viewer)
            ->assertSee('disabled', false)
            ->call('togglePost', $this->website->id, 'Instagram')
            ->assertForbidden();

        $this->assertFalse($this->profile('Instagram')['post_done']);

        $nobody = User::factory()->create();
        Livewire::actingAs($nobody)->test(SmmTrackerSection::class, ['project' => $this->project])->assertForbidden();
    }

    public function test_a_website_of_another_company_cannot_be_ticked(): void
    {
        $other = Website::create(['project_id' => Project::factory()->create()->id, 'name' => 'Other', 'url' => 'https://other.example.com', 'socials' => [['network' => 'Instagram', 'url' => null, 'status' => 'created']]]);

        try {
            $this->tracker()->call('togglePost', $other->id, 'Instagram');
        } catch (ModelNotFoundException) {
            // 404
        }

        $this->assertFalse(collect($other->fresh()->socialProfiles())->firstWhere('network', 'Instagram')['post_done']);
    }

    public function test_empty_states_point_to_the_websites_tab(): void
    {
        $this->website->update(['socials' => null]);

        $this->tracker()
            ->assertSee('No social networks on this website yet.')
            ->assertSee(route('projects.show', $this->project).'?tab=websites', false);

        $empty = Project::factory()->create();

        Livewire::actingAs($this->worker)->test(SmmTrackerSection::class, ['project' => $empty])
            ->assertSee('This company has no websites yet.');
    }

    public function test_editing_the_website_keeps_the_checklist_and_removing_a_network_drops_it(): void
    {
        $manager = User::factory()->create()->assignRole('manager');
        $this->tracker()->call('togglePost', $this->website->id, 'Instagram')->call('togglePost', $this->website->id, 'Twitter/X');

        Livewire::actingAs($manager)->test(WebsitesSection::class, ['project' => $this->project])
            ->call('edit', $this->website->id)
            ->set('name', 'Shop renamed')
            ->set('socials.instagram.url', 'https://instagram.com/new-shop')
            ->call('toggleSocial', 'Twitter/X')
            ->call('save')
            ->assertHasNoErrors();

        $profiles = collect($this->website->fresh()->socialProfiles());

        $instagram = $profiles->firstWhere('network', 'Instagram');
        $this->assertSame('https://instagram.com/new-shop', $instagram['url']);
        $this->assertTrue($instagram['post_done']);
        $this->assertSame($this->worker->id, $instagram['post_done_by']);

        $this->assertNull($profiles->firstWhere('network', 'Twitter/X'), 'a removed network takes its checklist with it');
    }

    public function test_the_smm_sub_tab_shows_the_tracker_and_keeps_the_old_post_log(): void
    {
        $manager = User::factory()->create()->assignRole('manager');

        Livewire::actingAs($manager)->test(OperationsSection::class, ['project' => $this->project])
            ->assertSee('Post log')
            ->assertSee('Schedule SMM Post');
    }
}
