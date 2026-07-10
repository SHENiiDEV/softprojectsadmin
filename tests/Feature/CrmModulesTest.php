<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Credential;
use App\Models\Boarding;
use App\Models\Report;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CrmModulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create()->assignRole('admin');
        $this->project = Project::factory()->create([
            'manager_id' => $this->user->id,
        ]);
    }

    public function test_can_manage_credentials_with_encryption(): void
    {
        $this->actingAs($this->user);

        // 1. Create credential
        Livewire::test(\App\Livewire\Projects\CredentialsSection::class, ['project' => $this->project])
            ->set('type', 'hosting')
            ->set('provider_url', 'https://hosting.com')
            ->set('login', 'my-login')
            ->set('password', 'secret-password-123')
            ->set('comments', 'Main cPanel access')
            ->call('save')
            ->assertHasNoErrors();

        // Assert database has the record
        $this->assertDatabaseHas('credentials', [
            'project_id' => $this->project->id,
            'type' => 'hosting',
            'login' => 'my-login',
        ]);

        $credential = Credential::where('project_id', $this->project->id)->first();
        $this->assertNotNull($credential);

        // Verify that in the raw database the password is encrypted (does not contain cleartext)
        $rawRow = DB::table('credentials')->where('id', $credential->id)->first();
        $this->assertNotEquals('secret-password-123', $rawRow->password);

        // Verify that Eloquent automatically decrypts it
        $this->assertEquals('secret-password-123', $credential->password);

        // 2. Edit credential
        Livewire::test(\App\Livewire\Projects\CredentialsSection::class, ['project' => $this->project])
            ->call('edit', $credential->id)
            ->assertSet('password', 'secret-password-123')
            ->set('password', 'new-secure-password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('new-secure-password', $credential->refresh()->password);

        // 3. Delete credential
        Livewire::test(\App\Livewire\Projects\CredentialsSection::class, ['project' => $this->project])
            ->call('delete', $credential->id);

        $this->assertDatabaseMissing('credentials', [
            'id' => $credential->id,
        ]);
    }

    public function test_boarding_is_initialized_and_can_be_updated(): void
    {
        $this->actingAs($this->user);

        $this->assertDatabaseMissing('boardings', [
            'project_id' => $this->project->id,
        ]);

        // Mounting the component should auto-initialize boarding record
        Livewire::test(\App\Livewire\Projects\BoardingSection::class, ['project' => $this->project])
            ->assertSet('cfs_verification', 'need_to_complete')
            ->set('cfs_verification', 'completed')
            ->set('bank_verification', 'pending')
            ->set('kyb_completed_at', '2026-05-25')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('boardings', [
            'project_id' => $this->project->id,
            'cfs_verification' => 'completed',
            'bank_verification' => 'pending',
            'kyb_completed_at' => '2026-05-25',
        ]);
    }

    public function test_reports_can_be_saved_and_calculates_days(): void
    {
        $this->actingAs($this->user);

        // Set due dates relative to today
        $now = now()->startOfDay();
        $accountsDue = $now->copy()->addDays(15)->format('Y-m-d'); // 15 days left (critical: < 30)
        $statementsDue = $now->copy()->addDays(40)->format('Y-m-d'); // 40 days left (normal: > 30)

        Livewire::test(\App\Livewire\Projects\ReportsSection::class, ['project' => $this->project])
            ->set('reg_number', '123456')
            ->set('auth_code', 'ABC999')
            ->set('ch_pass', 'pass123')
            ->set('accounts_due_by', $accountsDue)
            ->set('statements_due_by', $statementsDue)
            ->call('save')
            ->assertHasNoErrors()
            ->assertViewHas('daysUntilAccounts', 15)
            ->assertViewHas('daysUntilStatements', 40);

        $this->assertDatabaseHas('reports', [
            'project_id' => $this->project->id,
            'reg_number' => '123456',
            'auth_code' => 'ABC999',
            'ch_pass' => 'pass123',
            'accounts_due_by' => $accountsDue,
            'statements_due_by' => $statementsDue,
        ]);
    }
}
