<?php

namespace Tests\Feature;

use App\Livewire\Projects\CredentialsSection;
use App\Models\Credential;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CredentialCustomNameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_service_name_field_appears_only_for_other_type(): void
    {
        $manager = User::factory()->create()->assignRole('manager');
        $project = Project::factory()->create();

        Livewire::actingAs($manager)
            ->test(CredentialsSection::class, ['project' => $project])
            ->call('openCreateForm')
            ->assertDontSee('Service / Account Name')
            ->set('type', 'other')
            ->assertSee('Service / Account Name');
    }

    public function test_other_type_requires_a_service_name(): void
    {
        $manager = User::factory()->create()->assignRole('manager');
        $project = Project::factory()->create();

        Livewire::actingAs($manager)
            ->test(CredentialsSection::class, ['project' => $project])
            ->call('openCreateForm')
            ->set('type', 'other')
            ->set('login', 'ops@example.com')
            ->set('password', 'secret')
            ->call('save')
            ->assertHasErrors(['name' => 'required_if']);

        $this->assertSame(0, Credential::count());
    }

    public function test_custom_service_name_is_saved_shown_and_suggested(): void
    {
        $manager = User::factory()->create()->assignRole('manager');
        $project = Project::factory()->create();

        $component = Livewire::actingAs($manager)
            ->test(CredentialsSection::class, ['project' => $project])
            ->call('openCreateForm')
            ->set('type', 'other')
            ->set('name', 'PayAlly')
            ->set('login', 'ops@example.com')
            ->set('password', 'secret')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('PayAlly');

        $this->assertDatabaseHas('credentials', [
            'project_id' => $project->id,
            'type' => 'other',
            'name' => 'PayAlly',
        ]);

        $otherProject = Project::factory()->create();

        Livewire::actingAs($manager)
            ->test(CredentialsSection::class, ['project' => $otherProject])
            ->call('openCreateForm')
            ->set('type', 'other')
            ->assertSeeHtml('<option value="PayAlly">');
    }

    public function test_editing_keeps_the_existing_name_and_other_types_do_not_need_one(): void
    {
        $manager = User::factory()->create()->assignRole('manager');
        $project = Project::factory()->create();
        $credential = Credential::create([
            'project_id' => $project->id,
            'type' => 'hosting',
            'name' => 'Imported label',
            'login' => 'root',
            'password' => 'secret',
        ]);

        Livewire::actingAs($manager)
            ->test(CredentialsSection::class, ['project' => $project])
            ->call('edit', $credential->id)
            ->set('login', 'admin')
            ->call('save')
            ->assertHasNoErrors();

        $credential->refresh();
        $this->assertSame('admin', $credential->login);
        $this->assertSame('Imported label', $credential->name);
    }
}
