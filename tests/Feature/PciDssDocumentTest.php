<?php

namespace Tests\Feature;

use App\Livewire\PciDss\Index;
use App\Models\Client;
use App\Models\PciDssDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PciDssDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create()->assignRole('admin');
        $this->client = Client::create([
            'name' => 'Acme Payments Corp',
            'hash' => 'acme_test_hash_123',
        ]);
    }

    public function test_guest_cannot_access_pci_dss_vault(): void
    {
        $response = $this->get(route('pci-dss.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_pci_dss_vault(): void
    {
        $response = $this->actingAs($this->user)->get(route('pci-dss.index'));
        $response->assertStatus(200);
        $response->assertSee('PCI DSS');
        $response->assertSee('Compliance Vault');
    }

    public function test_root_view_displays_client_folders(): void
    {
        PciDssDocument::create([
            'client_id' => $this->client->id,
            'document_type' => 'AOC',
            'title' => 'AOC Document',
            'file_path' => 'pci_dss_documents/aoc.pdf',
            'file_name' => 'aoc.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertSee('Client Folders')
            ->assertSee('Acme Payments Corp')
            ->assertSee('1 document');
    }

    public function test_user_can_open_and_close_client_folder(): void
    {
        PciDssDocument::create([
            'client_id' => $this->client->id,
            'document_type' => 'AOC',
            'title' => 'Confidential AOC Acme',
            'file_path' => 'pci_dss_documents/aoc.pdf',
            'file_name' => 'aoc.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('openFolder', $this->client->id)
            ->assertSet('selectedClientId', $this->client->id)
            ->assertSee('Confidential AOC Acme')
            ->assertSee('Add Document')
            ->call('closeFolder')
            ->assertSet('selectedClientId', null)
            ->assertSee('Client Folders');
    }

    public function test_user_can_upload_asv_document(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('ASV_Scan_Report_Q3.pdf', 350, 'application/pdf');

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('openUploadModal', $this->client->id)
            ->set('uploadClientId', $this->client->id)
            ->set('uploadType', 'ASV')
            ->set('uploadTitle', 'Q3 ASV Vulnerability Scan')
            ->set('uploadValidUntil', '2027-09-30')
            ->set('uploadNotes', 'Approved ASV Scan')
            ->set('uploadFile', $file)
            ->call('uploadDocument')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pci_dss_documents', [
            'client_id' => $this->client->id,
            'document_type' => 'ASV',
            'title' => 'Q3 ASV Vulnerability Scan',
        ]);

        $doc = PciDssDocument::where('title', 'Q3 ASV Vulnerability Scan')->first();
        $this->assertNotNull($doc);
        $this->assertEquals('ASV', $doc->display_type);
    }

    public function test_legacy_scan_displays_and_filters_as_asv(): void
    {
        $doc = PciDssDocument::create([
            'client_id' => $this->client->id,
            'document_type' => 'Scan',
            'title' => 'Legacy Network Scan',
            'file_path' => 'pci_dss_documents/scan.pdf',
            'file_name' => 'scan.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        // Model display_type outputs ASV
        $this->assertEquals('ASV', $doc->display_type);

        // Filter by ASV finds the legacy scan
        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('openFolder', $this->client->id)
            ->set('filterType', 'ASV')
            ->assertSee('Legacy Network Scan');
    }

    public function test_user_can_upload_document_with_other_category_and_custom_type(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('PenTest_Report.pdf', 800, 'application/pdf');

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('openUploadModal', $this->client->id)
            ->set('uploadClientId', $this->client->id)
            ->set('uploadType', 'Other')
            ->set('uploadCustomType', 'External Penetration Test')
            ->set('uploadTitle', 'Annual External Network PenTest')
            ->set('uploadFile', $file)
            ->call('uploadDocument')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pci_dss_documents', [
            'client_id' => $this->client->id,
            'document_type' => 'Other',
            'custom_type' => 'External Penetration Test',
            'title' => 'Annual External Network PenTest',
        ]);

        $doc = PciDssDocument::where('custom_type', 'External Penetration Test')->first();
        $this->assertEquals('External Penetration Test', $doc->display_type);
    }

    public function test_upload_fails_when_other_category_has_no_custom_type(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('openUploadModal', $this->client->id)
            ->set('uploadClientId', $this->client->id)
            ->set('uploadType', 'Other')
            ->set('uploadCustomType', '')
            ->set('uploadFile', $file)
            ->call('uploadDocument')
            ->assertHasErrors(['uploadCustomType' => 'required_if']);
    }

    public function test_user_can_filter_documents_by_client_and_type(): void
    {
        $client2 = Client::create(['name' => 'Beta Fintech', 'hash' => 'beta_hash_456']);

        PciDssDocument::create([
            'client_id' => $this->client->id,
            'document_type' => 'ASV',
            'title' => 'Q1 ASV Vulnerability Scan',
            'file_path' => 'pci_dss_documents/scan1.pdf',
            'file_name' => 'scan1.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        PciDssDocument::create([
            'client_id' => $client2->id,
            'document_type' => 'SAQ',
            'title' => 'SAQ-A Questionnaire',
            'file_path' => 'pci_dss_documents/saq.pdf',
            'file_name' => 'saq.pdf',
            'file_size' => 2048,
            'mime_type' => 'application/pdf',
        ]);

        // Filter by Client 1 (open folder)
        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('openFolder', $this->client->id)
            ->assertSee('Q1 ASV Vulnerability Scan')
            ->assertDontSee('SAQ-A Questionnaire');

        // Filter by Type 'SAQ' across all files
        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->set('rootView', 'flat')
            ->set('filterType', 'SAQ')
            ->assertSee('SAQ-A Questionnaire')
            ->assertDontSee('Q1 ASV Vulnerability Scan');
    }

    public function test_user_can_search_documents(): void
    {
        PciDssDocument::create([
            'client_id' => $this->client->id,
            'document_type' => 'Agreement gateway',
            'title' => 'Cardaq Merchant Agreement',
            'file_path' => 'pci_dss_documents/agree.pdf',
            'file_name' => 'agree.pdf',
            'file_size' => 5000,
            'mime_type' => 'application/pdf',
        ]);

        PciDssDocument::create([
            'client_id' => $this->client->id,
            'document_type' => 'ASV',
            'title' => 'ASV Quarterly Report',
            'file_path' => 'pci_dss_documents/asv.pdf',
            'file_name' => 'asv.pdf',
            'file_size' => 3000,
            'mime_type' => 'application/pdf',
        ]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('openFolder', $this->client->id)
            ->set('search', 'Cardaq')
            ->assertSee('Cardaq Merchant Agreement')
            ->assertDontSee('ASV Quarterly Report');
    }

    public function test_user_can_edit_document_metadata(): void
    {
        $doc = PciDssDocument::create([
            'client_id' => $this->client->id,
            'document_type' => 'PCI DSS',
            'title' => 'Draft Certificate',
            'file_path' => 'pci_dss_documents/cert.pdf',
            'file_name' => 'cert.pdf',
            'file_size' => 4000,
            'mime_type' => 'application/pdf',
            'valid_until' => '2026-12-31',
        ]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('openEditModal', $doc->id)
            ->set('editTitle', 'Final Certified PCI DSS Level 1')
            ->set('editValidUntil', '2027-12-31')
            ->set('editNotes', 'Renewed by Trustwave')
            ->call('saveEdit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pci_dss_documents', [
            'id' => $doc->id,
            'title' => 'Final Certified PCI DSS Level 1',
            'notes' => 'Renewed by Trustwave',
        ]);
        $this->assertEquals('2027-12-31', $doc->fresh()->valid_until->format('Y-m-d'));
    }

    public function test_user_can_download_document(): void
    {
        Storage::fake('local');

        $filePath = 'pci_dss_documents/test_download.pdf';
        Storage::disk('local')->put($filePath, 'dummy content for download');

        $doc = PciDssDocument::create([
            'client_id' => $this->client->id,
            'document_type' => 'AOC',
            'title' => 'Downloadable AOC',
            'file_path' => $filePath,
            'file_name' => 'test_download.pdf',
            'file_size' => 26,
            'mime_type' => 'application/pdf',
        ]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('downloadDocument', $doc->id)
            ->assertFileDownloaded('test_download.pdf');
    }

    public function test_user_can_delete_document(): void
    {
        Storage::fake('local');

        $filePath = 'pci_dss_documents/file_to_delete.pdf';
        Storage::disk('local')->put($filePath, 'to be deleted');

        $doc = PciDssDocument::create([
            'client_id' => $this->client->id,
            'document_type' => 'ASV',
            'title' => 'Obsolete Scan',
            'file_path' => $filePath,
            'file_name' => 'file_to_delete.pdf',
            'file_size' => 100,
            'mime_type' => 'application/pdf',
        ]);

        $this->assertTrue(Storage::disk('local')->exists($filePath));

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('confirmDelete', $doc->id)
            ->call('deleteDocument');

        $this->assertDatabaseMissing('pci_dss_documents', [
            'id' => $doc->id,
        ]);

        $this->assertFalse(Storage::disk('local')->exists($filePath));
    }
}
