<?php

namespace Tests\Feature;

use App\Livewire\ClientPortal;
use App\Livewire\Clients\Index;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\TrafficLaunch;
use App\Models\User;
use App\Models\Website;
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

        Livewire::test(Index::class)
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

        Livewire::test(ClientPortal::class, ['hash' => $client->hash])
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

        Livewire::test(ClientPortal::class, ['hash' => $client->hash])
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

    public function test_client_portal_prefills_traffic_data_for_active_websites(): void
    {
        $client = Client::create([
            'name' => 'Traffic Client',
            'hash' => 'traffichash123456789012345678901',
        ]);

        $company = Project::factory()->create([
            'name' => 'Traffic Company',
            'client_id' => $client->id,
        ]);

        $website1 = Website::create([
            'project_id' => $company->id,
            'name' => 'Alpha Site',
            'url' => 'https://alpha.com',
            'status' => 'Live',
        ]);

        $website2 = Website::create([
            'project_id' => $company->id,
            'name' => 'Beta Site',
            'url' => 'https://beta.org',
            'status' => 'Live',
        ]);

        $component = Livewire::test(ClientPortal::class, ['hash' => $client->hash]);
        $rows = $component->instance()->getPrefilledTrafficData('October 2026');

        $this->assertCount(2, $rows);
        $domains = array_column($rows, 1);
        $this->assertContains('alpha.com', $domains);
        $this->assertContains('beta.org', $domains);
    }

    public function test_client_portal_save_traffic_launch_creates_records_and_tasks(): void
    {
        $client = Client::create([
            'name' => 'Launch Client',
            'hash' => 'launchhash1234567890123456789012',
        ]);

        $company = Project::factory()->create([
            'name' => 'Launch Company',
            'client_id' => $client->id,
        ]);

        $website = Website::create([
            'project_id' => $company->id,
            'name' => 'Gamma Site',
            'url' => 'https://gamma.io',
            'status' => 'Live',
        ]);

        $rows = [
            [
                '01.10.2026-31.10.2026', // Date
                'gamma.io',              // Domain
                '1000 UV/day',           // Plan
                'USA 70%, GBR 30%',      // GEO
                '40%',                   // BR
                '3',                     // Pages
                '30',                    // Time
                '10%',                   // Referral
                'https://ref.com',       // Ref links
                '20%',                   // Social
                'https://fb.com',        // Social links
                '50%',                   // Organic
                '20%',                   // Direct
                'seo keywords',          // Keys
                'Please launch on 1st',  // Comment
                'Pending',               // Status
            ],
        ];

        Livewire::test(ClientPortal::class, ['hash' => $client->hash])
            ->set('trafficTargetMonth', 'October 2026')
            ->call('saveTrafficLaunch', $rows, 'October 2026')
            ->assertDispatched('notify');

        // Check TrafficLaunch model record
        $this->assertDatabaseHas('traffic_launches', [
            'client_id' => $client->id,
            'website_id' => $website->id,
            'target_month' => 'October 2026',
            'domain' => 'gamma.io',
            'plan' => '1000 UV/day',
            'geo' => 'USA 70%, GBR 30%',
        ]);

        // Check Task created
        $this->assertDatabaseHas('tasks', [
            'project_id' => $company->id,
            'title' => '🚀 Traffic Launch: gamma.io (October 2026)',
            'status' => 'todo',
            'priority' => 'high',
        ]);
    }

    public function test_client_portal_copy_previous_month_traffic(): void
    {
        $client = Client::create([
            'name' => 'Copy Client',
            'hash' => 'copyhash123456789012345678901234',
        ]);

        $company = Project::factory()->create([
            'name' => 'Copy Company',
            'client_id' => $client->id,
        ]);

        Website::create([
            'project_id' => $company->id,
            'name' => 'Delta Site',
            'url' => 'https://delta.com',
            'status' => 'Live',
        ]);

        // Seed previous month traffic record
        TrafficLaunch::create([
            'client_id' => $client->id,
            'project_id' => $company->id,
            'target_month' => 'September 2026',
            'domain' => 'delta.com',
            'plan' => '500 UV/day',
            'geo' => 'CHE 100%',
            'bounce_rate' => '45%',
            'pages' => '2',
            'time_on_page' => '20',
            'status' => 'Completed',
        ]);
    }

    public function test_client_portal_exact_payload_save(): void
    {
        $client = Client::create([
            'name' => 'Exact Client',
            'hash' => 'exacthash12345678901234567890123',
        ]);

        $company = Project::factory()->create([
            'name' => 'Cybraxo Company',
            'client_id' => $client->id,
        ]);

        Website::create([
            'project_id' => $company->id,
            'name' => 'Cybraxo Site',
            'url' => 'https://cybraxo.co.uk',
            'status' => 'Live',
        ]);

        Website::create([
            'project_id' => $company->id,
            'name' => 'MakeMy CV',
            'url' => 'https://makemy-cv.co.uk',
            'status' => 'Live',
        ]);

        $rows = [
            ['01.09.2026-30.09.2026', 'cybraxo.co.uk', '20', 'DEU - 100%', '50-60%', '2-3', '15-30', '0%', '', '0%', '', '0%', '0%', '', '', 'Pending'],
            ['01.09.2026-30.09.2026', 'makemy-cv.co.uk', '15', 'SWE - 100%', '50-60%', '2-3', '15-30', '0%', '', '0%', '', '0%', '0%', '', '', 'Pending'],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
        ];

        Livewire::test(ClientPortal::class, ['hash' => $client->hash])
            ->call('saveTrafficLaunch', $rows)
            ->assertHasNoErrors();
    }
}
