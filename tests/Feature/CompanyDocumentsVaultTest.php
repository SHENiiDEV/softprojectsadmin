<?php

namespace Tests\Feature;

use App\Livewire\Projects\DocumentsSection;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyDocumentsVaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_and_categorize_document_for_specific_acquirer(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $project = Project::factory()->create();

        $file = UploadedFile::fake()->create('KYB_Package_Cardaq.zip', 500, 'application/zip');

        Livewire::actingAs($user)
            ->test(DocumentsSection::class, ['project' => $project])
            ->set('selectedAcquirer', 'Cardaq')
            ->set('selectedCategory', 'KYB PACK (.zip)')
            ->set('files', [$file])
            ->call('uploadDocuments')
            ->assertHasNoErrors();

        $media = $project->fresh()->getMedia('company_documents')->first();

        $this->assertNotNull($media);
        $this->assertEquals('Cardaq', $media->getCustomProperty('acquirer'));
        $this->assertEquals('KYB PACK (.zip)', $media->getCustomProperty('category'));
    }

    public function test_can_edit_document_metadata_and_filter_by_acquirer(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $project = Project::factory()->create();

        $file1 = UploadedFile::fake()->create('Statement_Cardaq.pdf', 200, 'application/pdf');
        $file2 = UploadedFile::fake()->create('Passport_UBO.pdf', 100, 'application/pdf');

        Livewire::actingAs($user)
            ->test(DocumentsSection::class, ['project' => $project])
            ->set('selectedAcquirer', 'Cardaq')
            ->set('selectedCategory', 'Bank Statements')
            ->set('files', [$file1])
            ->call('uploadDocuments')
            ->set('selectedAcquirer', 'CFS')
            ->set('selectedCategory', 'ID / Passports')
            ->set('files', [$file2])
            ->call('uploadDocuments');

        $media1 = $project->fresh()->getMedia('company_documents')->where('name', 'Statement_Cardaq')->first();

        // Edit metadata
        Livewire::actingAs($user)
            ->test(DocumentsSection::class, ['project' => $project])
            ->call('editDocumentMetadata', $media1->id)
            ->set('editAcquirer', 'Payabl')
            ->set('editCategory', 'Processing Statements')
            ->call('saveDocumentMetadata');

        $media1Fresh = $project->fresh()->getMedia('company_documents')->where('name', 'Statement_Cardaq')->first();
        $this->assertEquals('Payabl', $media1Fresh->getCustomProperty('acquirer'));
        $this->assertEquals('Processing Statements', $media1Fresh->getCustomProperty('category'));

        // Test filtering
        Livewire::actingAs($user)
            ->test(DocumentsSection::class, ['project' => $project])
            ->set('filterAcquirer', 'CFS')
            ->assertSee('Passport_UBO.pdf')
            ->assertDontSee('Statement_Cardaq.pdf');
    }
}
