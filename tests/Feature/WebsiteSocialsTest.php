<?php

namespace Tests\Feature;

use App\Livewire\GlobalSearch;
use App\Livewire\Projects\WebsitesSection;
use App\Models\Project;
use App\Models\User;
use App\Models\Website;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WebsiteSocialsTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->manager = User::factory()->create()->assignRole('manager');
        $this->project = Project::factory()->create();
    }

    private function section(?User $user = null)
    {
        return Livewire::actingAs($user ?? $this->manager)->test(WebsitesSection::class, ['project' => $this->project]);
    }

    public function test_networks_can_be_selected_with_link_and_status_and_are_saved_on_the_website(): void
    {
        $this->section()
            ->call('openCreateForm')
            ->set('name', 'Shop')
            ->set('url', 'https://shop.example.com')
            ->call('toggleSocial', 'Instagram')
            ->call('toggleSocial', 'Twitter/X')
            ->assertSet('socials.instagram.network', 'Instagram')
            ->assertSet('socials.instagram.status', 'not_created')
            ->assertSet('socials.twitter-x.network', 'Twitter/X')
            ->set('socials.instagram.url', 'https://instagram.com/shop')
            ->set('socials.instagram.status', 'created')
            ->call('save')
            ->assertHasNoErrors();

        $website = Website::where('url', 'https://shop.example.com')->firstOrFail();

        $this->assertSame([
            ['network' => 'Instagram', 'url' => 'https://instagram.com/shop', 'status' => 'created'],
            ['network' => 'Twitter/X', 'url' => null, 'status' => 'not_created'],
        ], $website->socials);
        $this->assertCount(2, $website->socialProfiles());
    }

    public function test_selecting_a_network_again_unselects_it(): void
    {
        $this->section()
            ->call('openCreateForm')
            ->call('toggleSocial', 'Facebook')
            ->assertSet('socials.facebook.network', 'Facebook')
            ->call('toggleSocial', 'Facebook')
            ->assertSet('socials', []);
    }

    public function test_custom_networks_can_be_added_and_do_not_duplicate_listed_ones(): void
    {
        $this->section()
            ->call('openCreateForm')
            ->set('newSocialName', 'Threads')
            ->call('addCustomSocial')
            ->assertSet('socials.threads.network', 'Threads')
            ->assertSet('newSocialName', '')
            ->assertSee('Threads')
            // typing a listed network in other letters selects the listed one, once
            ->set('newSocialName', 'instagram')
            ->call('addCustomSocial')
            ->set('newSocialName', 'INSTAGRAM')
            ->call('addCustomSocial')
            ->assertSet('socials.instagram.network', 'Instagram')
            ->assertCount('socials', 2);

        // a blank or far too long name is refused
        $this->section()->call('openCreateForm')
            ->set('newSocialName', '   ')->call('addCustomSocial')->assertHasErrors('newSocialName')
            ->set('newSocialName', str_repeat('x', 60))->call('addCustomSocial')->assertHasErrors('newSocialName');
    }

    public function test_existing_networks_are_loaded_for_editing_and_can_be_changed(): void
    {
        $website = Website::create([
            'project_id' => $this->project->id,
            'name' => 'Shop',
            'url' => 'https://shop.example.com',
            'socials' => [['network' => 'LinkedIn', 'url' => 'https://linkedin.com/company/shop', 'status' => 'created']],
        ]);

        $this->section()
            ->call('edit', $website->id)
            ->assertSet('socials.linkedin.url', 'https://linkedin.com/company/shop')
            ->assertSet('socials.linkedin.status', 'created')
            ->set('socials.linkedin.status', 'not_created')
            ->call('toggleSocial', 'YouTube')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['LinkedIn', 'YouTube'], array_column($website->fresh()->socials, 'network'));
        $this->assertSame('not_created', $website->fresh()->socials[0]['status']);
    }

    public function test_invalid_link_or_status_is_rejected(): void
    {
        $this->section()
            ->call('openCreateForm')
            ->set('name', 'Shop')
            ->set('url', 'https://shop.example.com')
            ->call('toggleSocial', 'Instagram')
            ->set('socials.instagram.url', 'not a link')
            ->set('socials.instagram.status', 'maybe')
            ->call('save')
            ->assertHasErrors(['socials.instagram.url', 'socials.instagram.status']);

        $this->assertSame(0, Website::count());
    }

    public function test_only_users_who_manage_projects_can_change_networks(): void
    {
        $worker = User::factory()->create()->assignRole('worker');

        $this->section($worker)->call('toggleSocial', 'Instagram')->assertForbidden();
        $this->section($worker)->set('newSocialName', 'Threads')->call('addCustomSocial')->assertForbidden();
    }

    public function test_the_website_list_shows_networks_with_their_status(): void
    {
        Website::create([
            'project_id' => $this->project->id,
            'name' => 'Shop',
            'url' => 'https://shop.example.com',
            'socials' => [
                ['network' => 'Instagram', 'url' => 'https://instagram.com/shop', 'status' => 'created'],
                ['network' => 'TikTok', 'url' => null, 'status' => 'not_created'],
            ],
        ]);

        $this->section()
            ->assertSee('Instagram')
            ->assertSee('TikTok')
            ->assertSee('https://instagram.com/shop', false)
            ->assertSee('Not created')
            ->assertSee('fa-instagram', false);
    }

    public function test_search_finds_websites_and_companies_by_social_network(): void
    {
        Website::create([
            'project_id' => $this->project->id,
            'name' => 'Shop',
            'url' => 'https://shop.example.com',
            'socials' => [['network' => 'Pinterest', 'url' => null, 'status' => 'created']],
        ]);

        $results = Livewire::actingAs($this->manager)->test(GlobalSearch::class)->set('query', 'pinterest')->get('results');

        $this->assertSame(['Shop'], array_column($results['websites'], 'title'));
        $this->assertSame([$this->project->name], array_column($results['companies'], 'title'));
        $this->assertStringContainsString('Website:', $results['companies'][0]['subtitle']);
    }
}
