<?php

namespace Tests\Feature;

use App\Livewire\Clients\Index as ClientsIndex;
use App\Livewire\CredentialVault;
use App\Livewire\PciDss\Index as PciDssIndex;
use App\Livewire\Projects\CredentialsSection;
use App\Livewire\Projects\WebsitesSection;
use App\Livewire\Settings\EmailTemplates;
use App\Livewire\Tariffs\Index as TariffsIndex;
use App\Models\Client;
use App\Models\Credential;
use App\Models\Project;
use App\Models\Provider;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModuleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $curator;

    protected User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->manager = User::factory()->create()->assignRole('manager');
        $this->curator = User::factory()->create()->assignRole('curator');
        $this->worker = User::factory()->create()->assignRole('worker');

        config([
            'features.clients' => true,
            'features.pci_dss' => true,
            'features.tariffs' => true,
            'features.credential_vault' => true,
            'features.settings_panel' => true,
        ]);
    }

    public function test_worker_is_forbidden_from_restricted_module_routes(): void
    {
        $this->actingAs($this->worker);

        foreach (['clients.index', 'pci-dss.index', 'tariffs.index', 'credentials', 'settings'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
    }

    public function test_manager_cannot_open_settings_but_can_open_business_modules(): void
    {
        $this->actingAs($this->manager);

        $this->get(route('settings'))->assertForbidden();

        foreach (['clients.index', 'pci-dss.index', 'tariffs.index', 'credentials'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_admin_can_open_settings(): void
    {
        $this->actingAs($this->admin)->get(route('settings'))->assertOk();
    }

    public function test_worker_cannot_mount_restricted_components(): void
    {
        $this->actingAs($this->worker);

        Livewire::test(ClientsIndex::class)->assertForbidden();
        Livewire::test(TariffsIndex::class)->assertForbidden();
        Livewire::test(PciDssIndex::class)->assertForbidden();
        Livewire::test(CredentialVault::class)->assertForbidden();
    }

    public function test_curator_cannot_mutate_tariffs(): void
    {
        $provider = Provider::create(['name' => 'Acme', 'code' => 'acme', 'type' => 'acquiring']);

        Livewire::actingAs($this->curator)
            ->test(TariffsIndex::class)
            ->assertOk()
            ->call('deleteProvider', $provider->id)
            ->assertForbidden();

        $this->assertDatabaseHas('providers', ['id' => $provider->id]);
    }

    public function test_manager_can_delete_provider(): void
    {
        $provider = Provider::create(['name' => 'Acme', 'code' => 'acme', 'type' => 'acquiring']);

        Livewire::actingAs($this->manager)
            ->test(TariffsIndex::class)
            ->call('deleteProvider', $provider->id)
            ->assertOk();

        $this->assertDatabaseMissing('providers', ['id' => $provider->id]);
    }

    public function test_curator_cannot_modify_credentials_in_vault_or_project(): void
    {
        $project = Project::factory()->create();
        $credential = Credential::create([
            'project_id' => $project->id,
            'name' => 'Hosting',
            'type' => 'hosting',
            'login' => 'root',
            'password' => 'secret',
        ]);

        Livewire::actingAs($this->curator)
            ->test(CredentialVault::class)
            ->assertOk()
            ->call('openCreateModal')
            ->assertForbidden();

        Livewire::actingAs($this->curator)
            ->test(CredentialsSection::class, ['project' => $project])
            ->call('delete', $credential->id)
            ->assertForbidden();

        $this->assertDatabaseHas('credentials', ['id' => $credential->id]);
    }

    public function test_curator_cannot_delete_pci_documents_but_can_view(): void
    {
        Livewire::actingAs($this->curator)
            ->test(PciDssIndex::class)
            ->assertOk()
            ->call('openUploadModal')
            ->assertForbidden();
    }

    public function test_curator_can_manage_clients_but_worker_cannot(): void
    {
        $client = Client::factory()->create();

        Livewire::actingAs($this->curator)
            ->test(ClientsIndex::class)
            ->call('deleteClient', $client->id)
            ->assertOk();

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_worker_cannot_manage_project_websites(): void
    {
        $project = Project::factory()->create();

        Livewire::actingAs($this->worker)
            ->test(WebsitesSection::class, ['project' => $project])
            ->call('openCreateForm')
            ->assertForbidden();
    }

    public function test_user_without_manage_tasks_cannot_edit_email_templates(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('view_tasks');

        Livewire::actingAs($viewer)
            ->test(EmailTemplates::class)
            ->call('openModal')
            ->assertForbidden();
    }
}
