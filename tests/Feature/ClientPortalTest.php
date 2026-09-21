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

    public function test_client_portal_traffic_launch_supports_multiline_geo_and_fields(): void
    {
        $client = Client::create([
            'name' => 'Multiline Client',
            'hash' => 'multilinehash123456789012345678',
        ]);

        $company = Project::factory()->create([
            'name' => 'Multiline Corp',
            'client_id' => $client->id,
        ]);

        Website::create([
            'project_id' => $company->id,
            'name' => 'Atlas Site',
            'url' => 'https://atlas.com',
            'status' => 'Live',
        ]);

        $multilineGeo = "USA 60%\nGBR 20%\nDEU 20%";
        $multilineKeys = "casino online\nbest slots";
        $multilineComment = "Line 1 note\nLine 2 note";

        $rows = [
            [
                '01.10.2026-31.10.2026',
                'atlas.com',
                '1000 UV/day',
                $multilineGeo,
                '50-60%',
                '2-3',
                '15-30',
                '10%',
                "https://ref1.com\nhttps://ref2.com",
                '5%',
                "https://t.me/channel\nhttps://fb.com/page",
                '50%',
                '35%',
                $multilineKeys,
                $multilineComment,
                'Active',
            ],
        ];

        $component = Livewire::test(ClientPortal::class, ['hash' => $client->hash])
            ->set('trafficTargetMonth', 'October 2026')
            ->call('saveTrafficLaunch', $rows, 'October 2026')
            ->assertDispatched('notify');

        // Verify TrafficLaunch record in DB contains exact newlines
        $this->assertDatabaseHas('traffic_launches', [
            'client_id' => $client->id,
            'domain' => 'atlas.com',
            'target_month' => 'October 2026',
            'geo' => $multilineGeo,
            'keywords' => $multilineKeys,
            'comment' => $multilineComment,
        ]);

        // Verify created Task has nl2br formatted lines in description
        $task = Task::where('project_id', $company->id)
            ->where('title', 'like', '%atlas.com%')
            ->first();

        $this->assertNotNull($task);
        $this->assertStringContainsString('USA 60%<br />', $task->description);
        $this->assertStringContainsString('GBR 20%<br />', $task->description);
        $this->assertStringContainsString('DEU 20%', $task->description);
        $this->assertStringContainsString('casino online<br />', $task->description);

        // Verify getPrefilledTrafficData preserves multiline GEO
        $prefilled = $component->instance()->getPrefilledTrafficData('October 2026');
        $this->assertNotEmpty($prefilled);
        $atlasRow = collect($prefilled)->first(fn ($r) => ($r[1] ?? '') === 'atlas.com');
        $this->assertNotNull($atlasRow);
        $this->assertEquals($multilineGeo, $atlasRow[3]);

        // Verify copying from previous month preserves multiline GEO
        $nextMonth = 'November 2026';
        Livewire::test(ClientPortal::class, ['hash' => $client->hash])
            ->set('trafficTargetMonth', $nextMonth)
            ->call('copyPreviousMonthTraffic')
            ->assertDispatched('traffic-data-loaded', function ($event, $params) use ($multilineGeo) {
                $rows = $params['data'] ?? [];
                $copiedAtlas = collect($rows)->first(fn ($r) => ($r[1] ?? '') === 'atlas.com');

                return $copiedAtlas && $copiedAtlas[3] === $multilineGeo;
            });
    }

    public function test_client_portal_switching_month_dispatches_traffic_data_loaded_with_target_month_data(): void
    {
        $client = Client::create([
            'name' => 'Month Switch Client',
            'hash' => 'switchmonth123456789012345678901',
        ]);

        $company = Project::factory()->create([
            'name' => 'Switch Company',
            'client_id' => $client->id,
        ]);

        Website::create([
            'project_id' => $company->id,
            'name' => 'Switch Site',
            'url' => 'https://switchsite.com',
            'status' => 'Live',
        ]);

        TrafficLaunch::create([
            'client_id' => $client->id,
            'project_id' => $company->id,
            'target_month' => 'December 2026',
            'domain' => 'switchsite.com',
            'geo' => "FR 50%\nES 50%",
            'plan' => '500 UV/day',
            'status' => 'Active',
        ]);

        Livewire::test(ClientPortal::class, ['hash' => $client->hash])
            ->set('trafficTargetMonth', 'December 2026')
            ->assertDispatched('traffic-data-loaded', function ($event, $params) {
                $rows = $params['data'] ?? [];
                $siteRow = collect($rows)->first(fn ($r) => ($r[1] ?? '') === 'switchsite.com');

                return $siteRow && $siteRow[3] === "FR 50%\nES 50%" && $siteRow[2] === '500 UV/day';
            });
    }

    public function test_client_portal_save_traffic_launch_creates_consolidated_task_with_rich_description_and_updates_it(): void
    {
        $client = Client::create([
            'name' => 'Consolidated Client',
            'hash' => 'consolidated1234567890123456789',
        ]);

        $company = Project::factory()->create([
            'name' => 'Consolidated Company',
            'client_id' => $client->id,
        ]);

        Website::create([
            'project_id' => $company->id,
            'name' => 'Site One',
            'url' => 'https://site-one.com',
            'status' => 'Live',
        ]);

        Website::create([
            'project_id' => $company->id,
            'name' => 'Site Two',
            'url' => 'https://site-two.com',
            'status' => 'Live',
        ]);

        $rows = [
            [
                '01.01.2027-31.01.2027', // Date
                'site-one.com',          // Domain
                '1000 UV/day',           // Plan
                "US: 60%\nUK: 40%",      // GEO
                '45%',                   // BR
                '2-3',                   // Pages
                '25',                    // Time
                '15%',                   // Referral
                'https://ref1.com',      // Ref links
                '10%',                   // Social
                'https://fb.com/site1',  // Social links
                '50%',                   // Organic
                '25%',                   // Direct
                'casino, slots',         // Keys
                'Urgent launch',         // Comment
                'Pending',               // Status
            ],
            [
                '01.01.2027-31.01.2027', // Date
                'site-two.com',          // Domain
                '500 UV/day',            // Plan
                'DE: 100%',              // GEO
                '50%',                   // BR
                '2',                     // Pages
                '30',                    // Time
                '0%',                    // Referral
                '',                      // Ref links
                '0%',                    // Social
                '',                      // Social links
                '70%',                   // Organic
                '30%',                   // Direct
                'betting online',        // Keys
                'Standard launch',       // Comment
                'Pending',               // Status
            ],
        ];

        // 1. Initial save creates 1 consolidated task
        Livewire::test(ClientPortal::class, ['hash' => $client->hash])
            ->set('trafficTargetMonth', 'January 2027')
            ->call('saveTrafficLaunch', $rows, 'January 2027')
            ->assertDispatched('notify');

        $tasks = Task::where('project_id', $company->id)->get();
        $this->assertCount(1, $tasks);

        $task = $tasks->first();
        $this->assertEquals('🚀 Traffic Launch: January 2027 (2 websites)', $task->title);
        $this->assertStringContainsString('site-one.com', $task->description);
        $this->assertStringContainsString('site-two.com', $task->description);
        $this->assertStringContainsString('US: 60%<br />', $task->description);
        $this->assertStringContainsString('UK: 40%', $task->description);
        $this->assertStringContainsString('DE: 100%', $task->description);
        $this->assertStringContainsString('1000 UV/day', $task->description);
        $this->assertStringContainsString('500 UV/day', $task->description);

        // 2. Second save with updated GEO updates the existing task, no duplicate task created
        $rows[0][3] = "US: 80%\nUK: 20%";
        Livewire::test(ClientPortal::class, ['hash' => $client->hash])
            ->set('trafficTargetMonth', 'January 2027')
            ->call('saveTrafficLaunch', $rows, 'January 2027')
            ->assertDispatched('notify');

        $this->assertEquals(1, Task::where('project_id', $company->id)->count());
        $task->refresh();
        $this->assertStringContainsString('US: 80%<br />', $task->description);
        $this->assertStringContainsString('UK: 20%', $task->description);
    }
}
