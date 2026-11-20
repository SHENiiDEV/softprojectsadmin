<?php

namespace Tests\Feature;

use App\Livewire\Projects\Create;
use App\Livewire\Projects\Edit;
use App\Livewire\Projects\WebsitesSection;
use App\Models\Director;
use App\Models\Project;
use App\Models\User;
use App\Models\Website;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WebsiteResponsibleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_responsible_person_can_be_set_when_adding_and_editing_a_website(): void
    {
        $manager = User::factory()->create()->assignRole('manager');
        $owner = User::factory()->create(['name' => 'Olga Owner'])->assignRole('worker');
        $other = User::factory()->create()->assignRole('worker');
        $project = Project::factory()->create();

        $component = Livewire::actingAs($manager)
            ->test(WebsitesSection::class, ['project' => $project])
            ->set('name', 'Shop')
            ->set('url', 'https://shop.example.com')
            ->set('responsible_user_id', $owner->id)
            ->call('save')
            ->assertSee('Olga Owner');

        $website = Website::where('url', 'https://shop.example.com')->firstOrFail();
        $this->assertSame($owner->id, $website->responsible_user_id);

        $component->call('edit', $website->id)
            ->assertSet('responsible_user_id', $owner->id)
            ->set('responsible_user_id', $other->id)
            ->call('save');

        $this->assertSame($other->id, $website->fresh()->responsible_user_id);
    }

    public function test_unknown_user_is_rejected(): void
    {
        $manager = User::factory()->create()->assignRole('manager');
        $project = Project::factory()->create();

        Livewire::actingAs($manager)
            ->test(WebsitesSection::class, ['project' => $project])
            ->set('name', 'Shop')
            ->set('url', 'https://shop.example.com')
            ->set('responsible_user_id', 999999)
            ->call('save')
            ->assertHasErrors(['responsible_user_id']);
    }

    public function test_deleting_the_responsible_user_keeps_the_website(): void
    {
        $owner = User::factory()->create();
        $website = Website::create([
            'project_id' => Project::factory()->create()->id,
            'responsible_user_id' => $owner->id,
            'name' => 'Shop',
            'url' => 'https://shop.example.com',
        ]);

        $owner->delete();

        $this->assertNull($website->fresh()->responsible_user_id);
    }

    public function test_director_curator_text_is_edited_in_the_director_card_and_saved(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $project = Project::factory()->has(Director::factory(['name' => 'Jane']))->create(['ubo' => null]);

        Livewire::actingAs($admin)
            ->test(Edit::class, ['project' => $project])
            ->assertSee('Director Curator')
            ->assertDontSee('Curator / Manager')
            ->set('ubo', 'David (SuperVerify)')
            ->call('save');

        $this->assertSame('David (SuperVerify)', $project->fresh()->ubo);
    }

    public function test_create_form_has_no_internal_curator_select(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->assertSee('Director Curator')
            ->assertSee('Website Responsible')
            ->assertDontSee('Curator / Manager');
    }
}
