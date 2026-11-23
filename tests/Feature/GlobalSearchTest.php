<?php

namespace Tests\Feature;

use App\Livewire\GlobalSearch;
use App\Models\Boarding;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Credential;
use App\Models\Director;
use App\Models\PciDssDocument;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\Provider;
use App\Models\SupportTicket;
use App\Models\Task;
use App\Models\User;
use App\Models\Website;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        config([
            'features.clients' => true,
            'features.pci_dss' => true,
            'features.tariffs' => true,
            'features.credential_vault' => true,
            'features.notes_tab' => true,
            'features.company_comments' => true,
            'features.websites_tab' => true,
        ]);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->worker = User::factory()->create()->assignRole('worker');
    }

    /**
     * @return array<string, list<array<string, string>>>
     */
    private function search(User $user, string $query): array
    {
        return Livewire::actingAs($user)->test(GlobalSearch::class)->set('query', $query)->get('results');
    }

    /**
     * @param  list<array<string, string>>  $items
     * @return list<string>
     */
    private function titles(array $items): array
    {
        return array_column($items, 'title');
    }

    private function betterb(): Project
    {
        $client = Client::factory()->create(['name' => 'SterlingPay']);
        $project = Project::factory()->create(['name' => 'BETTERB LTD', 'client_id' => $client->id, 'ubo' => 'David (SuperVerify)', 'status' => 'onboarding']);
        Director::factory()->create(['project_id' => $project->id, 'name' => 'Kyle Paul Alan Betts']);
        Website::create(['project_id' => $project->id, 'name' => 'Main Website', 'url' => 'https://rust-skins.com/', 'gateways' => ['Madfin', 'Decta']]);
        Boarding::create(['project_id' => $project->id, 'providers_data' => ['1_PayAlly' => ['name' => 'PayAlly', 'boarding_status' => 'pending']]]);

        return $project;
    }

    public function test_company_is_found_by_name_director_curator_client_website_and_provider(): void
    {
        $project = $this->betterb();
        Project::factory()->create(['name' => 'Unrelated Corp', 'ubo' => 'Nobody']);

        foreach ([
            'betterb' => null,
            'kyle betts' => 'Director: Kyle Paul Alan Betts',
            'superverify' => 'Director Curator: David (SuperVerify)',
            'sterlingpay' => 'Client: SterlingPay',
            'rust-skins' => 'Website:',
            'decta' => 'Website:',
            'payally' => 'Provider:',
        ] as $query => $reason) {
            $companies = $this->search($this->admin, $query)['companies'];

            $this->assertSame(['BETTERB LTD'], $this->titles($companies), "query: {$query}");
            $this->assertSame(route('projects.show', $project), $companies[0]['url']);

            if ($reason !== null) {
                $this->assertStringContainsString($reason, $companies[0]['subtitle'], "query: {$query}");
            }
        }
    }

    public function test_several_words_must_all_match(): void
    {
        $this->betterb();

        $this->assertSame(['BETTERB LTD'], $this->titles($this->search($this->admin, 'betterb kyle superverify')['companies']));
        $this->assertSame([], $this->search($this->admin, 'betterb nobody-here')['companies']);
    }

    public function test_wildcards_are_searched_literally(): void
    {
        Project::factory()->create(['name' => 'Fifty 5% Ltd']);
        Project::factory()->create(['name' => 'Alpha 55 Ltd']);

        $this->assertSame(['Fifty 5% Ltd'], $this->titles($this->search($this->admin, '5%')['companies']));
    }

    public function test_short_queries_only_return_navigation(): void
    {
        $this->betterb();

        $results = $this->search($this->admin, 'b');

        $this->assertSame([], $results['companies']);
        $this->assertSame([], $results['tasks']);
    }

    public function test_tasks_are_found_by_text_status_type_company_and_assignee(): void
    {
        $project = $this->betterb();
        $owner = User::factory()->create(['name' => 'Gleb Kybowner'])->assignRole('worker');

        Task::create([
            'title' => 'Prepare package',
            'description' => '<p>Passport of the director is missing</p>',
            'project_id' => $project->id,
            'assigned_to' => $owner->id,
            'status' => 'todo',
            'priority' => 'high',
            'type' => 'kyb',
            'onboarding_status' => 'Waiting for update',
            'current_status' => 'PayAlly replied on 18.11',
        ]);
        Task::create(['title' => 'Other chore', 'status' => 'todo', 'priority' => 'low']);

        foreach (['prepare', 'passport', 'payally replied', 'kyb', 'gleb', 'betterb'] as $query) {
            $this->assertSame(['Prepare package'], $this->titles($this->search($this->admin, $query)['tasks']), "query: {$query}");
        }

        $task = $this->search($this->admin, 'prepare')['tasks'][0];
        $this->assertStringContainsString('KYB', $task['subtitle']);
        $this->assertStringContainsString('Waiting for update', $task['subtitle']);
        $this->assertStringContainsString('BETTERB LTD', $task['subtitle']);
    }

    public function test_comments_and_notes_are_found_by_keyword_with_a_snippet(): void
    {
        $project = $this->betterb();
        $task = Task::create(['title' => 'KYB package', 'project_id' => $project->id, 'status' => 'todo', 'priority' => 'low', 'type' => 'kyb']);

        Comment::create(['task_id' => $task->id, 'user_id' => $this->admin->id, 'content' => '<p>Madfin asked for the lease agreement of the office</p>']);
        Comment::create(['project_id' => $project->id, 'user_id' => $this->admin->id, 'content' => 'Company level: lease agreement signed']);
        ProjectNote::create(['project_id' => $project->id, 'user_id' => $this->admin->id, 'content' => 'Landlord phone for the lease']);

        $comments = $this->search($this->admin, 'lease')['comments'];

        $this->assertCount(3, $comments);
        $urls = array_column($comments, 'url');
        $this->assertContains(route('tasks.kanban', ['task_id' => $task->id]), $urls);
        $this->assertContains(route('projects.show', $project).'?tab=comments', $urls);
        $this->assertContains(route('projects.show', $project).'?tab=notes', $urls);

        $taskComment = collect($comments)->firstWhere('title', 'KYB package');
        $this->assertStringContainsString('lease agreement of the office', $taskComment['subtitle']);
        $this->assertStringNotContainsString('<p>', $taskComment['subtitle']);
    }

    public function test_websites_clients_tickets_documents_credentials_and_providers_are_found(): void
    {
        $project = $this->betterb();
        $task = Task::create(['title' => 'Refund request', 'project_id' => $project->id, 'status' => 'email_inbox', 'priority' => 'low']);

        SupportTicket::create(['gmail_thread_id' => 't-1', 'customer_email' => 'buyer@example.com', 'subject' => 'Where is my refund', 'status' => 'open', 'project_id' => $project->id, 'task_id' => $task->id]);
        PciDssDocument::factory()->create(['title' => 'Quarterly ASV scan', 'client_id' => $project->client_id]);
        Credential::create(['project_id' => $project->id, 'type' => 'other', 'name' => 'PayAlly portal', 'login' => 'ops@betterb.com', 'password' => 'top-secret-password']);
        Provider::create(['name' => 'Decta Acquiring', 'code' => 'decta', 'type' => 'acquiring']);

        $this->assertSame(['Main Website'], $this->titles($this->search($this->admin, 'rust-skins')['websites']));
        $this->assertSame(['SterlingPay'], $this->titles($this->search($this->admin, 'sterlingp')['clients']));
        $this->assertSame(['Where is my refund'], $this->titles($this->search($this->admin, 'buyer@example')['tickets']));
        $this->assertSame(['Quarterly ASV scan'], $this->titles($this->search($this->admin, 'asv scan')['documents']));
        $this->assertSame(['Decta Acquiring'], $this->titles($this->search($this->admin, 'decta acq')['providers']));

        $credentials = $this->search($this->admin, 'payally portal')['credentials'];
        $this->assertSame(['PayAlly portal'], $this->titles($credentials));
        $this->assertStringNotContainsString('top-secret-password', json_encode($credentials));
        $this->assertSame([], $this->search($this->admin, 'top-secret-password')['credentials']);
    }

    public function test_results_respect_permissions(): void
    {
        $project = $this->betterb();
        PciDssDocument::factory()->create(['title' => 'Betterb AOC', 'client_id' => $project->client_id]);
        Credential::create(['project_id' => $project->id, 'type' => 'other', 'name' => 'Betterb vault', 'login' => 'x', 'password' => 'y']);
        Provider::create(['name' => 'Betterb Pay', 'code' => 'bp', 'type' => 'acquiring']);
        User::factory()->create(['name' => 'Betterb Person'])->assignRole('worker');
        Task::create(['title' => 'Betterb task', 'status' => 'todo', 'priority' => 'low']);

        $worker = $this->search($this->worker, 'betterb');

        $this->assertNotEmpty($worker['companies']);
        $this->assertNotEmpty($worker['tasks']);
        foreach (['clients', 'documents', 'credentials', 'providers', 'users'] as $restricted) {
            $this->assertSame([], $worker[$restricted], $restricted);
        }

        $admin = $this->search($this->admin, 'betterb');
        foreach (['companies', 'tasks', 'documents', 'credentials', 'providers', 'users'] as $group) {
            $this->assertNotEmpty($admin[$group], $group);
        }
    }

    public function test_navigation_shortcuts_follow_the_menu_and_the_query(): void
    {
        config(['features.onboarding' => true]);

        $worker = $this->titles($this->search($this->worker, 'onboard')['shortcuts']);
        $this->assertContains('Onboarding Status', $worker);

        $this->assertNotContains('Clients', $this->titles($this->search($this->worker, 'clients')['shortcuts']));
        $this->assertContains('Clients', $this->titles($this->search($this->admin, 'clients')['shortcuts']));

        // empty query lists every page the user can open
        $this->assertContains('Dashboard', $this->titles($this->search($this->worker, '')['shortcuts']));
    }
}
