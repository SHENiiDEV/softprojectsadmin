<?php

namespace Tests\Feature;

use App\Livewire\CredentialVault;
use App\Models\Credential;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CredentialVaultTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $managerUser1;

    protected User $managerUser2;

    protected User $curatorUser;

    protected Project $project1;

    protected Project $project2;

    protected Credential $credential1;

    protected Credential $credential2;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->adminUser = User::factory()->create()->assignRole('admin');
        $this->curatorUser = User::factory()->create()->assignRole('curator');
        $this->managerUser1 = User::factory()->create()->assignRole('manager');
        $this->managerUser2 = User::factory()->create()->assignRole('manager');

        // Project 1 managed by managerUser1
        $this->project1 = Project::factory()->create([
            'name' => 'Project Alpha',
            'manager_id' => $this->managerUser1->id,
        ]);

        // Project 2 managed by managerUser2
        $this->project2 = Project::factory()->create([
            'name' => 'Project Beta',
            'manager_id' => $this->managerUser2->id,
        ]);

        // Credentials
        $this->credential1 = Credential::create([
            'project_id' => $this->project1->id,
            'name' => 'Hosting Alpha',
            'type' => 'hosting',
            'login' => 'user-alpha',
            'password' => 'pass-alpha',
        ]);

        $this->credential2 = Credential::create([
            'project_id' => $this->project2->id,
            'name' => 'DB Beta',
            'type' => 'database',
            'login' => 'user-beta',
            'password' => 'pass-beta',
        ]);
    }

    public function test_admin_can_see_all_credentials_and_sidebar_projects(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CredentialVault::class)
            ->assertViewHas('grouped', function ($grouped) {
                // Should see both Project Alpha and Project Beta
                $labels = $grouped->pluck('label')->toArray();

                return in_array('Project Alpha', $labels) && in_array('Project Beta', $labels);
            })
            ->assertSet('types', ['database', 'hosting']);
    }

    public function test_curator_can_see_all_credentials_and_sidebar_projects(): void
    {
        $this->actingAs($this->curatorUser);

        Livewire::test(CredentialVault::class)
            ->assertViewHas('grouped', function ($grouped) {
                $labels = $grouped->pluck('label')->toArray();

                return in_array('Project Alpha', $labels) && in_array('Project Beta', $labels);
            })
            ->assertSet('types', ['database', 'hosting']);
    }

    public function test_manager_can_only_see_their_own_project_credentials_and_projects(): void
    {
        $this->actingAs($this->managerUser1);

        Livewire::test(CredentialVault::class)
            ->assertViewHas('grouped', function ($grouped) {
                $labels = $grouped->pluck('label')->toArray();

                // Should see Project Alpha, but NOT Project Beta
                return in_array('Project Alpha', $labels) && ! in_array('Project Beta', $labels);
            })
            ->assertSet('types', ['hosting']); // ONLY hosting (credential 2 type database is hidden)
    }
}
