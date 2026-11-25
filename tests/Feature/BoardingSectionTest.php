<?php

namespace Tests\Feature;

use App\Livewire\CompanyHealthScore;
use App\Livewire\Projects\BoardingSection;
use App\Models\Boarding;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Website;
use App\Services\OnboardingBoard;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BoardingSectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected Project $project;

    protected Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->manager = User::factory()->create()->assignRole('manager');
        $this->project = Project::factory()->create(['manager_id' => null]);
        $this->website = Website::create([
            'project_id' => $this->project->id,
            'name' => 'Main',
            'url' => 'https://shop.example.com',
            'gateways' => ['PayAlly', 'Madfin'],
        ]);
    }

    private function key(string $gateway = 'PayAlly'): string
    {
        return "{$this->website->id}_{$gateway}";
    }

    public function test_form_has_only_status_dates_and_boarding_time(): void
    {
        Livewire::actingAs($this->manager)->test(BoardingSection::class, ['project' => $this->project])
            ->assertSee('PayAlly Boarding Status')
            ->assertSee('PayAlly Boarding Send Date')
            ->assertSee('PayAlly Boarding Complete Date')
            ->assertSee('Boarding time')
            ->assertDontSee('General Legal Procedures')
            ->assertDontSee('Registry Checks')
            ->assertDontSee('Verification Status')
            ->assertDontSee('KYB Send')
            ->assertDontSee('CFS')
            ->assertDontSee('Companies House')
            ->assertDontSee('Global Boarding Completed Date');
    }

    public function test_send_date_and_complete_date_fill_themselves_from_the_status(): void
    {
        $key = $this->key();

        $component = Livewire::actingAs($this->manager)->test(BoardingSection::class, ['project' => $this->project])
            ->assertSet("providers.{$key}.status", 'pending')
            ->assertSet("providers.{$key}.kyb_sent_at", null)
            ->assertSet("providers.{$key}.boarding_completed_at", null);

        $component->set("providers.{$key}.status", 'in_progress')
            ->assertSet("providers.{$key}.kyb_sent_at", today()->toDateString())
            ->assertSet("providers.{$key}.boarding_completed_at", null);

        $component->set("providers.{$key}.status", 'completed')
            ->assertSet("providers.{$key}.boarding_completed_at", today()->toDateString());

        // other providers are not touched
        $component->assertSet("providers.{$this->key('Madfin')}.kyb_sent_at", null);
    }

    public function test_existing_dates_are_never_overwritten_by_the_status(): void
    {
        $key = $this->key();

        Livewire::actingAs($this->manager)->test(BoardingSection::class, ['project' => $this->project])
            ->set("providers.{$key}.kyb_sent_at", '2026-10-01')
            ->set("providers.{$key}.boarding_completed_at", '2026-10-20')
            ->set("providers.{$key}.status", 'in_progress')
            ->set("providers.{$key}.status", 'completed')
            ->assertSet("providers.{$key}.kyb_sent_at", '2026-10-01')
            ->assertSet("providers.{$key}.boarding_completed_at", '2026-10-20');
    }

    public function test_boarding_time_is_shown_for_completed_and_running_boardings(): void
    {
        $key = $this->key();

        $component = Livewire::actingAs($this->manager)->test(BoardingSection::class, ['project' => $this->project])
            ->assertSee('Not started yet');

        $component->set("providers.{$key}.kyb_sent_at", today()->subDays(5)->toDateString())
            ->set("providers.{$key}.status", 'in_progress')
            ->assertSee('5 days so far');

        $component->set("providers.{$key}.kyb_sent_at", today()->subDays(12)->toDateString())
            ->set("providers.{$key}.status", 'completed')
            ->assertSee('Took 12 days');

        $component->set("providers.{$key}.boarding_completed_at", today()->subDays(11)->toDateString())
            ->assertSee('Took 1 day');

        $component->set("providers.{$key}.boarding_completed_at", today()->subDays(12)->toDateString())
            ->assertSee('Done the same day');

        // nothing to count without a send date
        $component->set("providers.{$key}.kyb_sent_at", null)->assertSee('Not started yet');
    }

    public function test_duration_helper_handles_edge_cases(): void
    {
        $this->assertNull(OnboardingBoard::duration(null, '2026-11-10', 'completed'));
        $this->assertNull(OnboardingBoard::duration('2026-11-10', null, 'completed'));
        $this->assertNull(OnboardingBoard::duration('2026-11-10', '2026-11-01', 'completed'), 'completed before it was sent');
        $this->assertNull(OnboardingBoard::duration('2026-11-10', '2026-11-12', 'pending'));
        $this->assertNull(OnboardingBoard::duration('not a date', '2026-11-12', 'completed'));
        $this->assertSame(['days' => 9, 'running' => false], OnboardingBoard::duration('2026-11-01', '2026-11-10', 'completed'));
        $this->assertSame(['days' => 0, 'running' => true], OnboardingBoard::duration(today()->toDateString(), null, 'in_progress'));
        $this->assertSame('Started today', OnboardingBoard::durationLabel(['days' => 0, 'running' => true]));
        $this->assertNull(OnboardingBoard::durationLabel(null));
    }

    public function test_save_stores_one_status_in_both_legacy_fields_and_keeps_other_data(): void
    {
        $key = $this->key();
        Boarding::create([
            'project_id' => $this->project->id,
            'providers_data' => [$key => [
                'name' => 'PayAlly', 'website_id' => $this->website->id, 'kyb_status' => 'verified',
                'boarding_status' => 'boarding_completed', 'verification_status' => 'pending', 'custom' => 'keep me',
            ]],
        ]);

        // two old, disagreeing fields are shown as one status
        $component = Livewire::actingAs($this->manager)->test(BoardingSection::class, ['project' => $this->project])
            ->assertSet("providers.{$key}.status", 'completed');

        $component->set("providers.{$key}.status", 'rejected')->call('save')->assertHasNoErrors();

        $entry = Boarding::where('project_id', $this->project->id)->firstOrFail()->providers_data[$key];
        $this->assertSame('rejected', $entry['boarding_status']);
        $this->assertSame('rejected', $entry['verification_status']);
        $this->assertSame('verified', $entry['kyb_status']);
        $this->assertSame('keep me', $entry['custom']);
        $this->assertArrayNotHasKey('status', $entry);

        $component->assertSet("providers.{$key}.status", 'rejected');
    }

    public function test_invalid_status_and_dates_are_rejected(): void
    {
        $key = $this->key();

        Livewire::actingAs($this->manager)->test(BoardingSection::class, ['project' => $this->project])
            ->set("providers.{$key}.status", 'verified-ish')
            ->set("providers.{$key}.kyb_sent_at", 'yesterday-ish')
            ->call('save')
            ->assertHasErrors(["providers.{$key}.status", "providers.{$key}.kyb_sent_at"]);
    }

    public function test_company_manager_is_notified_once_when_a_provider_is_completed(): void
    {
        $companyManager = User::factory()->create()->assignRole('manager');
        $this->project->update(['manager_id' => $companyManager->id]);
        $key = $this->key();

        $count = fn () => $companyManager->fresh()->notifications->filter(fn ($n) => ($n->data['type'] ?? null) === 'boarding_completed')->count();

        $component = Livewire::actingAs($this->manager)->test(BoardingSection::class, ['project' => $this->project])
            ->set("providers.{$key}.status", 'in_progress')
            ->call('save');
        $this->assertSame(0, $count());

        $component->set("providers.{$key}.status", 'completed')->call('save');
        $this->assertSame(1, $count());

        $component->call('save');
        $this->assertSame(1, $count());
    }

    public function test_only_users_who_manage_projects_can_save(): void
    {
        $worker = User::factory()->create()->assignRole('worker');
        $key = $this->key();

        Livewire::actingAs($worker)->test(BoardingSection::class, ['project' => $this->project])
            ->set("providers.{$key}.status", 'completed')
            ->call('save')
            ->assertForbidden();

        $this->assertNull(Boarding::where('project_id', $this->project->id)->first()?->providers_data);
    }

    public function test_provider_details_for_the_pdf_include_dates_and_duration(): void
    {
        $key = $this->key();
        Boarding::create([
            'project_id' => $this->project->id,
            'providers_data' => [$key => [
                'name' => 'PayAlly', 'website_name' => 'Main', 'boarding_status' => 'completed', 'verification_status' => 'completed',
                'kyb_sent_at' => '2026-11-01', 'boarding_completed_at' => '2026-11-10',
            ]],
        ]);

        $details = OnboardingBoard::providerDetails($this->project->fresh());

        $this->assertCount(1, $details);
        $this->assertSame('PayAlly', $details[0]['name']);
        $this->assertSame('Completed', $details[0]['status']);
        $this->assertSame('Took 9 days', $details[0]['duration']);
        $this->assertSame('2026-11-01', $details[0]['sent_at']);
    }

    public function test_health_score_uses_provider_boarding_and_the_kyb_task_instead_of_the_removed_fields(): void
    {
        $key = $this->key();
        Boarding::create([
            'project_id' => $this->project->id,
            'providers_data' => [$key => ['name' => 'PayAlly', 'boarding_status' => 'completed', 'verification_status' => 'completed']],
        ]);
        Task::create(['title' => 'KYB', 'project_id' => $this->project->id, 'status' => 'done', 'priority' => 'low', 'type' => 'kyb', 'onboarding_status' => 'Completed']);

        config(['features.health_score' => true]);

        Livewire::actingAs($this->manager)->test(CompanyHealthScore::class)
            ->assertViewHas('companies', function ($companies) {
                $checks = $companies->firstWhere('id', $this->project->id)->health['checks'];

                return $checks['KYB'] === true
                    && $checks['Onboarding'] === true
                    && $checks['Website'] === true
                    && ! array_key_exists('CFS', $checks)
                    && ! array_key_exists('Bank', $checks)
                    && ! array_key_exists('Companies House', $checks);
            });
    }
}
