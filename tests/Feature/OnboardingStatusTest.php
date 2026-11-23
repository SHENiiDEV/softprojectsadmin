<?php

namespace Tests\Feature;

use App\Livewire\Onboarding\Index as OnboardingIndex;
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
}
