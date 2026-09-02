<?php

namespace Tests\Feature;

use App\Livewire\Projects\Create;
use App\Livewire\Projects\Edit;
use App\Livewire\Projects\Index;
use App\Livewire\Projects\Show;
use App\Models\Director;
use App\Models\Project;
use App\Models\User;
use App\Models\Website;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_projects_index_page_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create()->assignRole('manager');

        $response = $this->actingAs($user)->get('/projects');

        $response->assertOk();
    }

    public function test_can_search_projects_in_list(): void
    {
        $user = User::factory()->create()->assignRole('manager');
        $this->actingAs($user);

        $project1 = Project::factory()->has(Director::factory())->create(['name' => 'Target Company Alpha']);
        $project2 = Project::factory()->has(Director::factory())->create(['name' => 'Other Company Beta']);

        Livewire::test(Index::class)
            ->set('search', 'Alpha')
            ->assertSee('Target Company Alpha')
            ->assertDontSee('Other Company Beta');
    }

    public function test_can_create_project_and_director(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        $this->actingAs($user);

        Livewire::test(Create::class)
            ->set('name', 'New Custom Co')
            ->set('website', 'https://customcompany.com')
            ->set('status', 'active')
            ->set('integration_status', 'in_progress')
            ->set('ubo', 'John Doe')
            ->set('mcc', '1234')
            ->set('phone_krisp', '+11111')
            ->set('phone_zadarma', '+22222')
            ->set('email_corporate', 'corp@customcompany.com')
            ->set('email_private', 'private@customcompany.com')
            ->set('notes', 'Some secret notes')
            ->set('manager_id', $user->id)
            ->set('director_name', 'Jane Director')
            ->set('director_fee_status', 'paid')
            ->set('director_managed_by', $user->id)
            ->call('save');

        $project = Project::where('name', 'New Custom Co')->first();
        $this->assertNotNull($project);
        $this->assertDatabaseHas('projects', [
            'name' => 'New Custom Co',
            'status' => 'active',
            'integration_status' => 'in_progress',
            'ubo' => 'John Doe',
            'mcc' => '1234',
        ]);

        $this->assertDatabaseHas('websites', [
            'project_id' => $project->id,
            'url' => 'https://customcompany.com',
        ]);

        $this->assertEquals(['Krisp' => '+11111', 'Zadarma' => '+22222'], $project->phones);
        $this->assertEquals(['Corporate' => 'corp@customcompany.com', 'Private' => 'private@customcompany.com'], $project->emails);

        $this->assertDatabaseHas('directors', [
            'project_id' => $project->id,
            'name' => 'Jane Director',
            'fee_paid_status' => 'paid',
            'managed_by' => $user->id,
        ]);
    }

    public function test_can_view_project_details(): void
    {
        $user = User::factory()->create()->assignRole('manager');
        $this->actingAs($user);

        $project = Project::factory()->has(Director::factory(['name' => 'Specific Director Name']))->create(['name' => 'Specific Company Profile']);

        $response = $this->get(route('projects.show', $project));
        $response->assertOk()
            ->assertSee('Specific Company Profile')
            ->assertSee('Specific Director Name');
    }

    public function test_can_edit_project_and_director(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        $this->actingAs($user);

        $project = Project::factory()->has(Director::factory())->create([
            'name' => 'Old Company Co',
            'status' => 'onboarding',
        ]);

        Website::create([
            'project_id' => $project->id,
            'name' => 'Main Website',
            'url' => 'https://oldsite.com',
        ]);

        Livewire::test(Edit::class, ['project' => $project])
            ->set('name', 'Updated Company Co')
            ->set('website', 'https://newsite.com')
            ->set('status', 'active')
            ->set('director_name', 'Updated Director Name')
            ->set('director_fee_status', 'paid')
            ->call('save')
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Company Co',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('websites', [
            'project_id' => $project->id,
            'url' => 'https://newsite.com',
        ]);

        $this->assertDatabaseHas('directors', [
            'project_id' => $project->id,
            'name' => 'Updated Director Name',
            'fee_paid_status' => 'paid',
        ]);
    }

    public function test_can_delete_project_from_index(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        $this->actingAs($user);

        $project = Project::factory()->has(Director::factory())->create(['name' => 'Company to Delete']);

        Livewire::test(Index::class)
            ->call('deleteProject', $project->id);

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
        $this->assertDatabaseMissing('directors', [
            'project_id' => $project->id,
        ]);
    }

    public function test_can_toggle_layout_between_table_and_grid(): void
    {
        $user = User::factory()->create()->assignRole('manager');
        $this->actingAs($user);

        Project::factory()->has(Director::factory())->create(['name' => 'Grid View Test Company']);

        Livewire::test(Index::class)
            ->assertSet('layout', 'table')
            ->assertSee('Company')
            ->set('layout', 'grid')
            ->assertSet('layout', 'grid')
            ->assertSee('Grid View Test Company')
            ->assertSee('View Details');
    }

    public function test_can_add_note_to_project_from_index(): void
    {
        $user = User::factory()->create()->assignRole('manager');
        $this->actingAs($user);

        $project = Project::factory()->has(Director::factory())->create(['name' => 'Company Alpha']);

        Livewire::test(Index::class)
            ->call('openNoteModal', $project->id)
            ->assertSet('showNoteModal', true)
            ->assertSet('noteProjectId', $project->id)
            ->assertSet('noteProjectName', $project->name)
            ->set('noteContent', 'This is a test note from index list page')
            ->call('saveNote')
            ->assertSet('showNoteModal', false);

        $this->assertDatabaseHas('project_notes', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'content' => 'This is a test note from index list page',
        ]);
    }

    public function test_can_add_note_to_project_from_details(): void
    {
        $user = User::factory()->create()->assignRole('manager');
        $this->actingAs($user);

        $project = Project::factory()->has(Director::factory())->create(['name' => 'Company Beta']);

        Livewire::test(Show::class, ['project' => $project])
            ->call('openNoteModal')
            ->assertSet('showNoteModal', true)
            ->set('noteContent', 'This is a test note from details page')
            ->call('saveNote')
            ->assertSet('showNoteModal', false)
            ->assertDispatched('note-added');

        $this->assertDatabaseHas('project_notes', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'content' => 'This is a test note from details page',
        ]);
    }
}
