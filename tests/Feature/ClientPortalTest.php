<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Website;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_clients_management_route_requires_auth_and_correct_role(): void
    {
        // Guests cannot access
        $this->get('/clients')->assertRedirect(route('login'));

        // Workers cannot access
        $worker = User::factory()->create()->assignRole('worker');
        $this->actingAs($worker)->get('/clients')->assertForbidden();

        // Admin/Manager/Curator can access
        $manager = User::factory()->create()->assignRole('manager');
        $this->actingAs($manager)->get('/clients')->assertOk();
    }

    public function test_can_create_client_and_generates_hash(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Clients\Index::class)
            ->set('name', 'APS Group')
            ->call('saveClient')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'name' => 'APS Group',
        ]);

        $client = Client::where('name', 'APS Group')->first();
        $this->assertNotNull($client);
        $this->assertNotEmpty($client->hash);
        $this->assertEquals(32, strlen($client->hash));
    }

    public function test_client_portal_loads_by_hash(): void
    {
        $client = Client::create([
            'name' => 'Chilly Client',
            'hash' => 'chillyhash1234567890123456789012',
        ]);

        $response = $this->get(route('client.portal', $client->hash));
        $response->assertOk()
            ->assertSee('Chilly Client')
            ->assertSee('Create Support Request');
    }

    public function test_portal_form_submission_creates_task_with_attachments(): void
    {
        Storage::fake('public');

        $client = Client::create([
            'name' => 'Marvli Client',
            'hash' => 'marvlihash1234567890123456789012',
        ]);

        $company = Project::factory()->create([
            'name' => 'Marvli Company',
            'client_id' => $client->id,
        ]);

        $website = Website::create([
            'project_id' => $company->id,
            'name' => 'Main Site',
            'url' => 'https://marvli.com',
            'status' => 'Live',
        ]);

        $file1 = UploadedFile::fake()->image('screenshot.png');
        $file2 = UploadedFile::fake()->create('spec.pdf', 100);

        Livewire::test(\App\Livewire\ClientPortal::class, ['hash' => $client->hash])
            ->set('selectedCompanyId', $company->id)
            ->set('selectedWebsiteId', $website->id)
            ->set('requestType', 'Bug Report')
            ->set('description', 'This is a description of the issue that has to be at least ten characters long.')
            ->set('attachments', [$file1, $file2])
            ->call('submitReport')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $company->id,
            'title' => '[Portal] Bug Report: Main Site',
            'description' => 'This is a description of the issue that has to be at least ten characters long.',
            'status' => 'todo',
            'priority' => 'high',
            'creator_id' => null,
        ]);

        $task = Task::where('project_id', $company->id)->first();
        $this->assertNotNull($task);

        // Verify attachments are loaded via media library
        $media = $task->getMedia('documents');
        $this->assertCount(2, $media);
        $this->assertEquals('screenshot', $media[0]->name);
        $this->assertEquals('spec', $media[1]->name);
    }

    public function test_portal_form_submission_respects_custom_priority(): void
    {
        Storage::fake('public');

        $client = Client::create([
            'name' => 'Priority Client',
            'hash' => 'priorityhash12345678901234567890',
        ]);

        $company = Project::factory()->create([
            'name' => 'Priority Company',
            'client_id' => $client->id,
        ]);

        $website = Website::create([
            'project_id' => $company->id,
            'name' => 'Main Site',
            'url' => 'https://priority.com',
            'status' => 'Live',
        ]);

        Livewire::test(\App\Livewire\ClientPortal::class, ['hash' => $client->hash])
            ->set('selectedCompanyId', $company->id)
            ->set('selectedWebsiteId', $website->id)
            ->set('requestType', 'General Question')
            ->set('urgency', 'critical')
            ->set('description', 'This is a description of the issue that has to be at least ten characters long.')
            ->call('submitReport')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $company->id,
            'title' => '[Portal] General Question: Main Site',
            'priority' => 'critical',
        ]);
    }
}
