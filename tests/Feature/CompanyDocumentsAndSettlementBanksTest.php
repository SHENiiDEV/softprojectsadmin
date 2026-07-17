<?php

namespace Tests\Feature;

use App\Livewire\Projects\Create;
use App\Livewire\Projects\DocumentsSection;
use App\Livewire\Projects\Edit;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyDocumentsAndSettlementBanksTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->user = User::factory()->create()->assignRole('admin');
    }

    public function test_can_create_and_edit_company_with_settlement_banks(): void
    {
        $client = Client::create(['name' => 'Test Client']);

        // 1. Create Company
        Livewire::actingAs($this->user)
            ->test(Create::class)
            ->set('name', 'Alpha Corp')
            ->set('director_name', 'John Doe')
            ->set('client_id', $client->id)
            ->set('settlement_bank_1', "LHV Bank\nIBAN: EE123\nSWIFT: LHVVEE2X")
            ->set('settlement_bank_2', "Wise\nIBAN: BE456\nSWIFT: WISEBEXX")
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('projects.index'));

        $project = Project::where('name', 'Alpha Corp')->first();
        $this->assertNotNull($project);
        $this->assertEquals("LHV Bank\nIBAN: EE123\nSWIFT: LHVVEE2X", $project->settlement_bank_1);
        $this->assertEquals("Wise\nIBAN: BE456\nSWIFT: WISEBEXX", $project->settlement_bank_2);

        // 2. Edit Company
        Livewire::actingAs($this->user)
            ->test(Edit::class, ['project' => $project])
            ->assertSet('settlement_bank_1', "LHV Bank\nIBAN: EE123\nSWIFT: LHVVEE2X")
            ->assertSet('settlement_bank_2', "Wise\nIBAN: BE456\nSWIFT: WISEBEXX")
            ->set('settlement_bank_1', 'Updated Bank 1')
            ->set('settlement_bank_2', 'Updated Bank 2')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('projects.index'));

        $project->refresh();
        $this->assertEquals('Updated Bank 1', $project->settlement_bank_1);
        $this->assertEquals('Updated Bank 2', $project->settlement_bank_2);

        // 3. View Show page
        $response = $this->actingAs($this->user)->get(route('projects.show', $project));
        $response->assertStatus(200)
            ->assertSee('Updated Bank 1')
            ->assertSee('Updated Bank 2');

        // 4. View PDF Report
        $pdfResponse = $this->actingAs($this->user)->get(route('projects.pdf', $project));
        $pdfResponse->assertStatus(200);
    }

    public function test_can_upload_and_delete_company_documents(): void
    {
        Storage::fake('public');

        $project = Project::factory()->create();

        $file1 = UploadedFile::fake()->create('contract.pdf', 500);
        $file2 = UploadedFile::fake()->create('kyb_doc.png', 300);

        // Upload files
        Livewire::actingAs($this->user)
            ->test(DocumentsSection::class, ['project' => $project])
            ->set('files', [$file1, $file2])
            ->call('uploadDocuments')
            ->assertHasNoErrors();

        $project->refresh();
        $documents = $project->getMedia('company_documents');
        $this->assertCount(2, $documents);
        $this->assertEquals('contract', $documents[0]->name);
        $this->assertEquals('kyb_doc', $documents[1]->name);

        // Delete a document
        Livewire::actingAs($this->user)
            ->test(DocumentsSection::class, ['project' => $project])
            ->call('deleteDocument', $documents[0]->id)
            ->assertHasNoErrors();

        $project->refresh();
        $this->assertCount(1, $project->getMedia('company_documents'));
    }
}
