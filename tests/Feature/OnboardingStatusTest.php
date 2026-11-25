<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\Onboarding\Index as OnboardingIndex;
use App\Livewire\Projects\OnboardingSection;
use App\Livewire\Tasks\KanbanBoard;
use App\Models\ActivityLog;
use App\Models\Boarding;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Director;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Website;
use App\Services\NavigationMenu;
use App\Services\OnboardingBoard;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OnboardingStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $curator;

    protected User $worker;

    protected User $kybOwner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        config(['features.onboarding' => true, 'features.kanban' => true]);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->manager = User::factory()->create()->assignRole('manager');
        $this->curator = User::factory()->create()->assignRole('curator');
        $this->worker = User::factory()->create()->assignRole('worker');
        $this->kybOwner = User::factory()->create(['name' => 'Gleb KYB'])->assignRole('worker');
    }

    private function company(string $name = 'Acme Holdings'): Project
    {
        $client = Client::factory()->create(['name' => 'SterlingPay']);
        $project = Project::factory()->create(['name' => $name, 'client_id' => $client->id, 'ubo' => 'David (SuperVerify)']);
        Director::factory()->create(['project_id' => $project->id, 'name' => 'Stefan Pisari']);
        Website::create(['project_id' => $project->id, 'name' => 'Main', 'url' => 'https://rust-skins.com/']);

        return $project;
    }

    private function task(Project $project, string $type, array $attributes = []): Task
    {
        return Task::create(array_merge([
            'title' => ucfirst($type).' for '.$project->name,
            'project_id' => $project->id,
            'status' => 'todo',
            'priority' => 'medium',
            'type' => $type,
            'onboarding_status' => 'Not started',
        ], $attributes));
    }

    public function test_new_tasks_default_to_general_type(): void
    {
        $task = Task::create(['title' => 'Plain', 'status' => 'todo', 'priority' => 'low']);

        $this->assertSame('general', $task->fresh()->type);
        $this->assertNull($task->fresh()->onboarding_status);
    }

    public function test_page_requires_login_and_task_access(): void
    {
        $this->get(route('onboarding.index'))->assertRedirect('/login');

        $this->actingAs($this->worker)->get(route('onboarding.index'))->assertOk();
    }

    public function test_page_is_hidden_when_the_feature_is_off(): void
    {
        config(['features.onboarding' => false]);

        $this->actingAs($this->admin)->get(route('onboarding.index'))->assertNotFound();
    }

    public function test_company_row_is_built_from_its_kyb_and_onboarding_tasks(): void
    {
        $project = $this->company();

        Boarding::create([
            'project_id' => $project->id,
            'providers_data' => [
                '1_PayAlly' => ['name' => 'PayAlly', 'boarding_status' => 'in_progress', 'verification_status' => 'verified'],
                '1_Madfin' => ['name' => 'Madfin', 'boarding_status' => 'pending'],
            ],
        ]);

        $kyb = $this->task($project, 'kyb', ['assigned_to' => $this->kybOwner->id, 'onboarding_status' => 'KYB in process']);
        $onboarding = $this->task($project, 'onboarding', ['onboarding_status' => 'Under review', 'current_status' => 'Sent PCI DSS docs to Madfin 13.11']);
        $this->task($project, 'general', ['title' => 'Unrelated chore', 'onboarding_status' => null]);

        Comment::create(['task_id' => $kyb->id, 'user_id' => $this->manager->id, 'content' => 'PayAlly asked for a new form']);

        $this->actingAs($this->manager)->get(route('onboarding.index'))
            ->assertOk()
            ->assertSee('ONB-'.str_pad((string) $project->id, 3, '0', STR_PAD_LEFT))
            ->assertSee('SterlingPay')
            ->assertSee('Acme Holdings')
            ->assertSee('Stefan Pisari')
            ->assertSee('David (SuperVerify)')
            ->assertSee('rust-skins.com')
            ->assertSee('Gleb KYB')
            ->assertSee('PayAlly')
            ->assertSee('Madfin')
            ->assertSee('Sent PCI DSS docs to Madfin 13.11')
            ->assertSee('PayAlly asked for a new form')
            ->assertDontSee('Unrelated chore');

        // One row, status of the Onboarding task wins, KYB status is shown beside it
        Livewire::actingAs($this->manager)->test(OnboardingIndex::class)
            ->assertViewHas('rows', function ($rows) use ($onboarding, $kyb) {
                return $rows->count() === 1
                    && $rows->first()['status'] === 'Under review'
                    && $rows->first()['primary_task_id'] === $onboarding->id
                    && $rows->first()['kyb_task_id'] === $kyb->id
                    && $rows->first()['kyb_status'] === 'KYB in process';
            });
    }

    public function test_companies_without_onboarding_tasks_and_archived_companies_are_not_listed(): void
    {
        $plain = $this->company('Plain Co');
        $this->task($plain, 'website', ['onboarding_status' => null]);

        $archived = $this->company('Archived Co');
        $this->task($archived, 'kyb');
        $archived->update(['archived_at' => now()]);

        Livewire::actingAs($this->manager)->test(OnboardingIndex::class)
            ->assertViewHas('rows', fn ($rows) => $rows->isEmpty())
            ->assertSee('No onboarding yet');
    }

    public function test_task_without_a_company_still_gets_its_own_row_until_it_is_linked(): void
    {
        $task = Task::create([
            'title' => 'LOOTERA IT MB',
            'description' => '<p>Бординг в PayDo - в процессе через Solvex</p>',
            'status' => 'review',
            'priority' => 'medium',
            'type' => 'onboarding',
            'onboarding_status' => 'Under review',
        ]);

        Livewire::actingAs($this->manager)->test(OnboardingIndex::class)
            ->assertSee('LOOTERA IT MB')
            ->assertSee('Not linked to a company')
            ->assertSee('TASK-'.$task->id)
            ->assertSee('Under review')
            ->assertSee('Бординг в PayDo - в процессе через Solvex')
            ->call('setStatus', $task->id, 'Approved')
            ->assertSee('Approved');

        $this->assertSame('Approved', $task->fresh()->onboarding_status);

        // Once the task is linked to a company, it joins that company's row
        $project = $this->company('Lootera IT MB');
        $task->update(['project_id' => $project->id]);

        Livewire::actingAs($this->manager)->test(OnboardingIndex::class)
            ->assertDontSee('Not linked to a company')
            ->assertSee('ONB-'.str_pad((string) $project->id, 3, '0', STR_PAD_LEFT))
            ->assertSee('Stefan Pisari');
    }

    public function test_clicking_a_row_opens_a_side_panel_with_tasks_comments_and_open_task_links(): void
    {
        $project = $this->company();
        $kyb = $this->task($project, 'kyb', ['title' => 'KYB package', 'assigned_to' => $this->kybOwner->id]);
        Comment::create(['task_id' => $kyb->id, 'user_id' => $this->manager->id, 'content' => '<p>Passport scan received</p>']);
        $code = 'ONB-'.str_pad((string) $project->id, 3, '0', STR_PAD_LEFT);

        Livewire::actingAs($this->manager)->test(OnboardingIndex::class)
            ->assertSet('selected', null)
            ->assertDontSee('Latest comments')
            ->call('select', $code)
            ->assertSet('selected', $code)
            ->assertSee('Latest comments')
            ->assertSee('Passport scan received')
            ->assertSee('KYB package')
            ->assertSee('Open task')
            ->assertSee(route('tasks.kanban', ['task_id' => $kyb->id]), false)
            ->assertSee(route('projects.show', $project), false)
            // no Onboarding task yet: a shortcut to create one for this company is offered
            ->assertSee(route('tasks.kanban', ['new' => 'onboarding', 'project_id' => $project->id]), false)
            ->call('closeDetail')
            ->assertSet('selected', null)
            ->assertDontSee('Latest comments');
    }

    public function test_panel_edits_the_current_status_and_curators_get_no_edit_controls(): void
    {
        $project = $this->company();
        $task = $this->task($project, 'onboarding');
        $code = 'ONB-'.str_pad((string) $project->id, 3, '0', STR_PAD_LEFT);

        Livewire::actingAs($this->manager)->test(OnboardingIndex::class)
            ->call('select', $code)
            ->assertSee('Saved automatically')
            ->call('saveCurrentStatus', $task->id, 'Docs sent to Madfin')
            ->assertSee('Current status saved.')
            ->assertSee('Docs sent to Madfin');

        Livewire::actingAs($this->curator)->test(OnboardingIndex::class)
            ->call('select', $code)
            ->assertDontSee('Saved automatically')
            ->assertDontSee('KYB task</a>', false);
    }

    public function test_kanban_new_shortcut_opens_a_prefilled_create_form(): void
    {
        $project = $this->company('Delta Ltd');

        Livewire::withQueryParams(['new' => 'kyb', 'project_id' => (string) $project->id])
            ->actingAs($this->manager)
            ->test(KanbanBoard::class)
            ->assertSet('showModal', true)
            ->assertSet('taskType', 'kyb')
            ->assertSet('taskOnboardingStatus', 'Not started')
            ->assertSet('taskProject', (string) $project->id)
            ->assertSet('taskTitle', 'KYB - Delta Ltd');

        // curators cannot create tasks, so the shortcut does nothing for them
        Livewire::withQueryParams(['new' => 'kyb'])
            ->actingAs($this->curator)
            ->test(KanbanBoard::class)
            ->assertSet('showModal', false);

        // unknown types are ignored
        Livewire::withQueryParams(['new' => 'bogus'])
            ->actingAs($this->manager)
            ->test(KanbanBoard::class)
            ->assertSet('showModal', false);
    }

    public function test_filters_and_search_narrow_the_table(): void
    {
        $first = $this->company('Alpha Ltd');
        $second = $this->company('Beta Ltd');

        $this->task($first, 'kyb', ['assigned_to' => $this->kybOwner->id, 'onboarding_status' => 'In progress']);
        $this->task($second, 'kyb', ['onboarding_status' => 'Approved']);

        $component = Livewire::actingAs($this->manager)->test(OnboardingIndex::class);

        $component->set('filterStatus', 'Approved')->assertSee('Beta Ltd')->assertDontSee('Alpha Ltd');
        $component->set('filterStatus', '')->set('filterResponsible', (string) $this->kybOwner->id)->assertSee('Alpha Ltd')->assertDontSee('Beta Ltd');
        $component->set('filterResponsible', '')->set('search', 'beta')->assertSee('Beta Ltd')->assertDontSee('Alpha Ltd');
        $component->call('resetFilters')->assertSee('Alpha Ltd')->assertSee('Beta Ltd');
    }

    public function test_status_and_current_status_can_be_edited_inline_and_are_logged(): void
    {
        $project = $this->company();
        $task = $this->task($project, 'onboarding');

        Livewire::actingAs($this->manager)->test(OnboardingIndex::class)
            ->call('setStatus', $task->id, 'Waiting for update')
            ->call('saveCurrentStatus', $task->id, '  16.11 form sent, waiting  ')
            ->assertHasNoErrors();

        $task->refresh();
        $this->assertSame('Waiting for update', $task->onboarding_status);
        $this->assertSame('16.11 form sent, waiting', $task->current_status);
        $this->assertTrue(ActivityLog::where('task_id', $task->id)->where('action', 'onboarding_status_updated')->exists());
    }

    public function test_invalid_status_is_ignored_and_other_task_types_cannot_be_edited(): void
    {
        $project = $this->company();
        $task = $this->task($project, 'onboarding');
        $general = $this->task($project, 'general', ['onboarding_status' => null]);

        $component = Livewire::actingAs($this->manager)->test(OnboardingIndex::class);

        $component->call('setStatus', $task->id, 'Totally made up');
        $this->assertSame('Not started', $task->fresh()->onboarding_status);

        try {
            $component->call('setStatus', $general->id, 'Completed');
        } catch (ModelNotFoundException) {
            // Only Onboarding / KYB tasks can be edited from this page.
        }

        $this->assertNull($general->fresh()->onboarding_status);
    }

    public function test_curator_can_view_but_not_edit(): void
    {
        $project = $this->company();
        $task = $this->task($project, 'onboarding');

        Livewire::actingAs($this->curator)->test(OnboardingIndex::class)
            ->assertOk()
            ->call('setStatus', $task->id, 'Completed')
            ->assertForbidden();

        $this->assertSame('Not started', $task->fresh()->onboarding_status);
    }

    public function test_menu_contains_onboarding_status_for_task_users(): void
    {
        $labels = collect(app(NavigationMenu::class)->groups($this->worker))
            ->flatMap(fn ($group) => $group['items'])
            ->pluck('label');

        $this->assertTrue($labels->contains('Onboarding Status'));
    }

    public function test_kanban_saves_type_and_onboarding_fields_only_for_onboarding_types(): void
    {
        $project = $this->company();

        Livewire::actingAs($this->manager)
            ->test(KanbanBoard::class)
            ->set('taskTitle', 'Prepare KYB')
            ->set('taskProject', (string) $project->id)
            ->set('taskType', 'kyb')
            ->set('taskOnboardingStatus', 'KYB in process')
            ->set('taskCurrentStatus', 'Waiting for director passport')
            ->set('taskPriority', 'high')
            ->set('taskStatus', 'todo')
            ->call('saveTask')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Prepare KYB',
            'type' => 'kyb',
            'onboarding_status' => 'KYB in process',
            'current_status' => 'Waiting for director passport',
        ]);

        Livewire::actingAs($this->manager)
            ->test(KanbanBoard::class)
            ->set('taskTitle', 'Fix header')
            ->set('taskType', 'website')
            ->set('taskOnboardingStatus', 'Completed')
            ->set('taskCurrentStatus', 'should be dropped')
            ->set('taskPriority', 'low')
            ->set('taskStatus', 'todo')
            ->call('saveTask')
            ->assertHasNoErrors();

        $website = Task::where('title', 'Fix header')->firstOrFail();
        $this->assertSame('website', $website->type);
        $this->assertNull($website->onboarding_status);
        $this->assertNull($website->current_status);
    }

    public function test_kanban_rejects_unknown_type_and_status(): void
    {
        Livewire::actingAs($this->manager)
            ->test(KanbanBoard::class)
            ->set('taskTitle', 'Bad')
            ->set('taskType', 'nonsense')
            ->set('taskPriority', 'low')
            ->set('taskStatus', 'todo')
            ->call('saveTask')
            ->assertHasErrors(['taskType']);

        Livewire::actingAs($this->manager)
            ->test(KanbanBoard::class)
            ->set('taskTitle', 'Bad status')
            ->set('taskType', 'kyb')
            ->set('taskOnboardingStatus', 'Whatever')
            ->set('taskPriority', 'low')
            ->set('taskStatus', 'todo')
            ->call('saveTask')
            ->assertHasErrors(['taskOnboardingStatus']);

        $this->assertDatabaseMissing('tasks', ['title' => 'Bad']);
    }

    public function test_kanban_can_be_filtered_by_type_and_shows_type_badge(): void
    {
        $project = $this->company();
        $this->task($project, 'kyb', ['title' => 'KYB package']);
        $this->task($project, 'pci_dss', ['title' => 'PCI scan', 'onboarding_status' => null]);

        Livewire::actingAs($this->manager)
            ->test(KanbanBoard::class)
            ->assertSee('KYB package')
            ->assertSee('PCI scan')
            ->set('filterType', 'kyb')
            ->assertSee('KYB package')
            ->assertDontSee('PCI scan');
    }

    public function test_panel_shows_who_changed_what_and_when(): void
    {
        $project = $this->company();
        $task = $this->task($project, 'onboarding');
        $other = $this->company('Other Co');
        $otherTask = $this->task($other, 'kyb');
        $code = 'ONB-'.str_pad((string) $project->id, 3, '0', STR_PAD_LEFT);

        $component = Livewire::actingAs($this->manager)->test(OnboardingIndex::class)
            ->call('select', $code)
            ->assertSee('No changes recorded yet.')
            ->call('setStatus', $task->id, 'Waiting for update')
            ->call('saveCurrentStatus', $task->id, '16.11 form sent to PayAlly')
            ->assertSee("Onboarding status changed from 'Not started' to 'Waiting for update'")
            ->assertSee('Current status updated: 16.11 form sent to PayAlly')
            ->assertSee($this->manager->name);

        // another company's changes never leak into this history
        Livewire::actingAs($this->manager)->test(OnboardingIndex::class)
            ->call('select', 'ONB-'.str_pad((string) $other->id, 3, '0', STR_PAD_LEFT))
            ->assertDontSee('Waiting for update')
            ->assertSee('No changes recorded yet.');

        $this->assertTrue(ActivityLog::where('task_id', $otherTask->id)->where('action', 'onboarding_status_updated')->doesntExist());

        // clearing the text is recorded as well
        $component->call('saveCurrentStatus', $task->id, '')->assertSee('Current status cleared');
    }

    public function test_panel_can_be_opened_from_a_link(): void
    {
        $project = $this->company();
        $this->task($project, 'kyb');
        $code = 'ONB-'.str_pad((string) $project->id, 3, '0', STR_PAD_LEFT);

        Livewire::withQueryParams(['open' => $code])
            ->actingAs($this->manager)
            ->test(OnboardingIndex::class)
            ->assertSet('selected', $code)
            ->assertSee('History');
    }

    public function test_company_tab_shows_status_tasks_history_and_deep_link(): void
    {
        $project = $this->company();
        $code = 'ONB-'.str_pad((string) $project->id, 3, '0', STR_PAD_LEFT);

        Livewire::actingAs($this->manager)->test(OnboardingSection::class, ['project' => $project])
            ->assertSee('No onboarding yet')
            ->assertSee(route('tasks.kanban', ['new' => 'kyb', 'project_id' => $project->id]), false);

        $kyb = $this->task($project, 'kyb', ['title' => 'KYB package', 'assigned_to' => $this->kybOwner->id, 'onboarding_status' => 'KYB in process']);

        Livewire::actingAs($this->manager)->test(OnboardingSection::class, ['project' => $project])
            ->assertDontSee('No onboarding yet')
            ->assertSee('KYB in process')
            ->assertSee('Gleb KYB')
            ->assertSee('KYB package')
            ->assertSee(route('tasks.kanban', ['task_id' => $kyb->id]), false)
            ->assertSee(route('onboarding.index', ['open' => $code]), false)
            ->call('setStatus', $kyb->id, 'Under review')
            ->assertSee('Onboarding status updated.')
            ->assertSee("changed from 'KYB in process' to 'Under review'");

        $this->assertSame('Under review', $kyb->fresh()->onboarding_status);
    }

    public function test_company_tab_is_read_only_for_curators_and_hidden_without_the_feature(): void
    {
        $project = $this->company();
        $kyb = $this->task($project, 'kyb');

        Livewire::actingAs($this->curator)->test(OnboardingSection::class, ['project' => $project])
            ->assertSee('Onboarding')
            ->assertDontSee('Saved automatically')
            ->call('setStatus', $kyb->id, 'Completed')
            ->assertForbidden();

        $this->actingAs($this->manager)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee("activeTab = 'onboarding'", false);

        config(['features.onboarding' => false]);

        $this->actingAs($this->manager)->get(route('projects.show', $project))
            ->assertOk()
            ->assertDontSee("activeTab = 'onboarding'", false);
    }

    public function test_dashboard_shows_the_onboarding_pipeline_and_my_open_onboardings(): void
    {
        config(['features.deadline_center' => true]);

        $mine = $this->company('Mine Ltd');
        $done = $this->company('Done Ltd');
        $notMine = $this->company('Theirs Ltd');

        $this->task($mine, 'kyb', ['assigned_to' => $this->manager->id, 'onboarding_status' => 'Waiting for update', 'current_status' => 'Waiting for PayAlly']);
        $this->task($done, 'kyb', ['assigned_to' => $this->manager->id, 'onboarding_status' => 'Completed']);
        $this->task($notMine, 'kyb', ['assigned_to' => $this->kybOwner->id, 'onboarding_status' => 'Blocked']);

        $code = 'ONB-'.str_pad((string) $mine->id, 3, '0', STR_PAD_LEFT);

        $this->actingAs($this->manager)->get('/dashboard')
            ->assertOk()
            ->assertSee('Onboarding')
            ->assertSee('2 in progress of 3 companies')
            ->assertSee('My onboardings')
            ->assertSee('Mine Ltd')
            ->assertSee('Waiting for PayAlly')
            ->assertSee(route('onboarding.index', ['open' => $code]), false)
            ->assertSee(route('onboarding.index', ['status' => 'Blocked']), false);

        // only unfinished onboardings that I am responsible for are listed under "My onboardings"
        Livewire::actingAs($this->manager)->test(Dashboard::class)
            ->assertViewHas('onboarding', fn (array $onboarding) => $onboarding['mine']->pluck('company')->all() === ['Mine Ltd']
                && $onboarding['total'] === 3
                && $onboarding['active'] === 2
                && $onboarding['counts']->all() === ['Waiting for update' => 1, 'Blocked' => 1, 'Completed' => 1]);
    }

    public function test_dashboard_hides_the_onboarding_block_when_there_is_nothing_or_the_feature_is_off(): void
    {
        $this->actingAs($this->manager)->get('/dashboard')->assertOk()->assertDontSee('My onboardings')->assertDontSee('companies</p>', false);

        $this->task($this->company(), 'kyb', ['assigned_to' => $this->manager->id]);
        config(['features.onboarding' => false]);

        $this->actingAs($this->manager)->get('/dashboard')->assertOk()->assertDontSee('My onboardings');
    }

    public function test_status_counts_follow_the_selected_responsible_client_and_search(): void
    {
        $other = User::factory()->create(['name' => 'Renat Other'])->assignRole('worker');

        $a = $this->company('Alpha Ltd');
        $b = $this->company('Beta Ltd');
        $c = $this->company('Gamma Ltd');

        $this->task($a, 'kyb', ['assigned_to' => $this->kybOwner->id, 'onboarding_status' => 'In progress']);
        $this->task($b, 'kyb', ['assigned_to' => $this->kybOwner->id, 'onboarding_status' => 'Approved']);
        $this->task($c, 'kyb', ['assigned_to' => $other->id, 'onboarding_status' => 'In progress']);

        $component = Livewire::actingAs($this->manager)->test(OnboardingIndex::class);

        $component->assertViewHas('total', 3)
            ->assertViewHas('statusCounts', fn ($counts) => $counts->sortKeys()->all() === ['Approved' => 1, 'In progress' => 2])
            ->assertSee('Gleb KYB (2)')
            ->assertSee('Renat Other (1)');

        // choosing a person narrows the numbers on top to that person's companies
        $component->set('filterResponsible', (string) $this->kybOwner->id)
            ->assertViewHas('total', 2)
            ->assertViewHas('statusCounts', fn ($counts) => $counts->sortKeys()->all() === ['Approved' => 1, 'In progress' => 1]);

        // the status filter narrows the table but not the chips, so the person's other statuses stay visible
        $component->set('filterStatus', 'Approved')
            ->assertViewHas('total', 2)
            ->assertViewHas('statusCounts', fn ($counts) => $counts->sortKeys()->all() === ['Approved' => 1, 'In progress' => 1])
            ->assertViewHas('rows', fn ($rows) => $rows->pluck('company')->all() === ['Beta Ltd']);

        $component->set('filterStatus', '')->set('filterResponsible', (string) $other->id)
            ->assertViewHas('total', 1)
            ->assertViewHas('statusCounts', fn ($counts) => $counts->sortKeys()->all() === ['In progress' => 1]);
    }

    public function test_provider_statuses_are_read_from_the_compliance_fields(): void
    {
        $project = $this->company();
        $this->task($project, 'kyb');

        Boarding::create([
            'project_id' => $project->id,
            'providers_data' => [
                '1_TPF' => ['name' => 'TPF', 'boarding_status' => 'pending', 'verification_status' => 'verified'],
                '1_Decta' => ['name' => 'Decta', 'boarding_status' => 'pending', 'verification_status' => 'pending'],
                '1_Madfin' => ['name' => 'Madfin', 'boarding_status' => 'boarding_completed', 'verification_status' => 'pending'],
                '1_PayAlly' => ['name' => 'PayAlly', 'boarding_status' => 'in_progress', 'verification_status' => 'pending'],
                '1_Rapyd' => ['name' => 'Rapyd', 'boarding_status' => 'pending', 'verification_status' => 'rejected'],
                '1_Vialet' => ['name' => 'Vialet', 'boarding_status' => 'need_to_complete', 'verification_status' => 'need_to_upload'],
            ],
        ]);

        $providers = collect(app(OnboardingBoard::class)->rowFor($project)['providers'])->pluck('status', 'name')->all();

        $this->assertSame([
            'TPF' => 'Completed',
            'Decta' => 'Pending',
            'Madfin' => 'Completed',
            'PayAlly' => 'In process',
            'Rapyd' => 'Decline',
            'Vialet' => 'Pending',
        ], $providers);
    }

    public function test_provider_status_can_be_changed_in_the_panel_and_is_stored_for_the_compliance_tab(): void
    {
        $project = $this->company();
        $this->task($project, 'kyb');
        $boarding = Boarding::create([
            'project_id' => $project->id,
            'providers_data' => [
                '7_PayAlly' => ['name' => 'PayAlly', 'website_id' => 7, 'boarding_status' => 'pending', 'verification_status' => 'pending', 'kyb_status' => 'sent'],
                '8_PayAlly' => ['name' => 'PayAlly', 'website_id' => 8, 'boarding_status' => 'pending', 'verification_status' => 'pending', 'kyb_status' => 'sent'],
                '7_Madfin' => ['name' => 'Madfin', 'boarding_status' => 'pending', 'verification_status' => 'pending'],
            ],
        ]);
        $code = 'ONB-'.str_pad((string) $project->id, 3, '0', STR_PAD_LEFT);

        Livewire::actingAs($this->manager)->test(OnboardingIndex::class)
            ->call('select', $code)
            ->assertSee('click a status to change it')
            ->assertSee('Decline')
            ->assertSee('In process')
            ->call('setProviderStatus', $project->id, 'PayAlly', 'in_process')
            ->assertSee('PayAlly: In process.')
            ->assertSee("Provider PayAlly: status changed from 'Pending' to 'In process'")
            ->call('setProviderStatus', $project->id, 'Madfin', 'decline')
            ->call('setProviderStatus', $project->id, 'PayAlly', 'completed');

        $data = $boarding->fresh()->providers_data;

        // every website entry of the provider is updated, with the values the Compliance tab understands
        foreach (['7_PayAlly', '8_PayAlly'] as $key) {
            $this->assertSame('completed', $data[$key]['boarding_status']);
            $this->assertSame('completed', $data[$key]['verification_status']);
            $this->assertSame('sent', $data[$key]['kyb_status']);
        }
        $this->assertSame('rejected', $data['7_Madfin']['verification_status']);
        $this->assertSame('rejected', $data['7_Madfin']['boarding_status']);

        $providers = collect(app(OnboardingBoard::class)->rowFor($project)['providers'])->pluck('status', 'name')->all();
        $this->assertSame(['PayAlly' => 'Completed', 'Madfin' => 'Decline'], $providers);

        $this->assertSame(3, ActivityLog::where('project_id', $project->id)->where('action', 'provider_status_updated')->count());
    }

    public function test_provider_from_website_gateways_gets_a_record_when_its_status_is_set(): void
    {
        $project = $this->company();
        $this->task($project, 'onboarding');
        $website = $project->websites()->first();
        $website->update(['gateways' => ['Decta']]);

        $this->assertSame('Pending', app(OnboardingBoard::class)->rowFor($project)['providers'][0]['status']);

        Livewire::actingAs($this->manager)->test(OnboardingIndex::class)
            ->call('setProviderStatus', $project->id, 'Decta', 'in_process');

        $entry = Boarding::where('project_id', $project->id)->firstOrFail()->providers_data["{$website->id}_Decta"];
        $this->assertSame('Decta', $entry['name']);
        $this->assertSame($website->id, $entry['website_id']);
        $this->assertSame('in_progress', $entry['verification_status']);
        $this->assertSame('In process', app(OnboardingBoard::class)->rowFor($project->fresh())['providers'][0]['status']);
    }

    public function test_provider_status_editing_is_protected(): void
    {
        $project = $this->company();
        $this->task($project, 'kyb');
        $boarding = Boarding::create(['project_id' => $project->id, 'providers_data' => ['1_PayAlly' => ['name' => 'PayAlly', 'boarding_status' => 'pending', 'verification_status' => 'pending']]]);
        $code = 'ONB-'.str_pad((string) $project->id, 3, '0', STR_PAD_LEFT);

        // curators see the providers but have no selectors and cannot call the action
        Livewire::actingAs($this->curator)->test(OnboardingIndex::class)
            ->call('select', $code)
            ->assertDontSee('click a status to change it')
            ->call('setProviderStatus', $project->id, 'PayAlly', 'completed')
            ->assertForbidden();

        // unknown status values are ignored
        Livewire::actingAs($this->manager)->test(OnboardingIndex::class)
            ->call('setProviderStatus', $project->id, 'PayAlly', 'verified-ish');

        $this->assertSame('pending', $boarding->fresh()->providers_data['1_PayAlly']['verification_status']);

        // a company that is not in the tracker cannot be edited through this page
        $untracked = $this->company('Untracked Co');

        try {
            Livewire::actingAs($this->manager)->test(OnboardingIndex::class)
                ->call('setProviderStatus', $untracked->id, 'PayAlly', 'completed');
        } catch (\Throwable) {
            // 404
        }

        $this->assertNull(Boarding::where('project_id', $untracked->id)->first());
    }

    public function test_company_tab_edits_provider_statuses_too(): void
    {
        $project = $this->company();
        $this->task($project, 'kyb');
        $boarding = Boarding::create(['project_id' => $project->id, 'providers_data' => ['1_PayAlly' => ['name' => 'PayAlly', 'boarding_status' => 'pending', 'verification_status' => 'pending']]]);

        Livewire::actingAs($this->manager)->test(OnboardingSection::class, ['project' => $project])
            ->assertSee('click a status to change it')
            ->call('setProviderStatus', $project->id, 'PayAlly', 'decline')
            ->assertSee('PayAlly: Decline.');

        $this->assertSame('rejected', $boarding->fresh()->providers_data['1_PayAlly']['verification_status']);
    }

    public function test_completing_a_provider_notifies_the_manager_and_sets_the_date_like_the_compliance_tab(): void
    {
        $companyManager = User::factory()->create()->assignRole('manager');
        $project = $this->company();
        $project->update(['manager_id' => $companyManager->id]);
        $this->task($project, 'kyb');
        $boarding = Boarding::create(['project_id' => $project->id, 'providers_data' => ['1_PayAlly' => ['name' => 'PayAlly', 'boarding_status' => 'pending', 'verification_status' => 'pending']]]);

        $completedNotifications = fn () => $companyManager->fresh()->notifications->filter(fn ($n) => ($n->data['type'] ?? null) === 'boarding_completed')->count();

        $component = Livewire::actingAs($this->admin)->test(OnboardingIndex::class);

        // moving between other statuses does not notify
        $component->call('setProviderStatus', $project->id, 'PayAlly', 'in_process');
        $this->assertSame(0, $completedNotifications());
        $this->assertSame(today()->toDateString(), $boarding->fresh()->providers_data['1_PayAlly']['kyb_sent_at']);

        $component->call('setProviderStatus', $project->id, 'PayAlly', 'completed');
        $this->assertSame(1, $completedNotifications());
        $this->assertSame(today()->toDateString(), $boarding->fresh()->providers_data['1_PayAlly']['boarding_completed_at']);

        // completed again (no change) and a repeated choice stay silent
        $component->call('setProviderStatus', $project->id, 'PayAlly', 'completed');
        $this->assertSame(1, $completedNotifications());

        // an existing date is kept when the status is set to completed once more later
        $boarding->update(['providers_data' => ['1_PayAlly' => array_merge($boarding->fresh()->providers_data['1_PayAlly'], ['boarding_completed_at' => '2026-10-01', 'boarding_status' => 'pending', 'verification_status' => 'pending'])]]);
        $component->call('setProviderStatus', $project->id, 'PayAlly', 'completed');
        $this->assertSame('2026-10-01', $boarding->fresh()->providers_data['1_PayAlly']['boarding_completed_at']);
    }
}
