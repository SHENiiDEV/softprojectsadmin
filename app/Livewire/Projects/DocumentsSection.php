<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentsSection extends Component
{
    use WithFileUploads;

    public Project $project;

    public $files = [];

    public string $selectedAcquirer = 'General';

    public string $customAcquirer = '';

    public string $selectedCategory = 'KYB PACK (.zip)';

    public string $filterAcquirer = '';

    public string $filterCategory = '';

    public ?int $editingDocId = null;

    public string $editAcquirer = 'General';

    public string $editCategory = 'KYB PACK (.zip)';

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function getAvailableAcquirersProperty(): array
    {
        $list = ['General'];

        // Add gateways from project websites
        if ($this->project->relationLoaded('websites') || $this->project->websites()->exists()) {
            foreach ($this->project->websites as $w) {
                if (is_array($w->gateways)) {
                    foreach ($w->gateways as $g) {
                        if (! empty($g) && ! in_array($g, $list, true)) {
                            $list[] = $g;
                        }
                    }
                }
            }
        }

        // Add provider from boarding
        if ($this->project->boarding && ! empty($this->project->boarding->provider_name)) {
            if (! in_array($this->project->boarding->provider_name, $list, true)) {
                $list[] = $this->project->boarding->provider_name;
            }
        }

        // Master defaults if list is small
        $defaults = ['Cardaq', 'CFS', 'Payabl', 'Bankera', 'Decta', 'Emerchantpay', 'ConnectPay'];
        foreach ($defaults as $d) {
            if (! in_array($d, $list, true)) {
                $list[] = $d;
            }
        }

        $list[] = 'Other';

        return array_values(array_unique($list));
    }

    public function getCategoriesListProperty(): array
    {
        return [
            'KYB PACK (.zip)',
            'Corporate Documents',
            'Processing Statements',
            'Bank Statements',
            'ID / Passports',
            'License / Utility Bills',
            'Agreements & Contracts',
            'Other',
        ];
    }

    public function uploadDocuments(): void
    {
        $this->validate([
            'files.*' => 'required|file|max:102400', // 100MB max per file to support large KYB zip packages
            'selectedAcquirer' => 'required|string',
            'selectedCategory' => 'required|string',
        ], [
            'files.*.max' => 'Each file must not exceed 100MB.',
        ]);

        $acquirer = ($this->selectedAcquirer === 'Other' && ! empty($this->customAcquirer))
            ? trim($this->customAcquirer)
            : $this->selectedAcquirer;

        foreach ($this->files as $file) {
            $this->project->addMedia($file)
                ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                ->withCustomProperties([
                    'acquirer' => $acquirer ?: 'General',
                    'category' => $this->selectedCategory ?: 'Other',
                ])
                ->toMediaCollection('company_documents');
        }

        $this->files = [];
        $this->customAcquirer = '';
        $this->project->refresh();

        session()->flash('message', 'Documents successfully uploaded and categorized.');
    }

    public function editDocumentMetadata(int $id): void
    {
        $media = $this->project->media()->findOrFail($id);
        $this->editingDocId = $id;
        $this->editAcquirer = $media->getCustomProperty('acquirer', 'General');
        $this->editCategory = $media->getCustomProperty('category', 'Other');
    }

    public function saveDocumentMetadata(): void
    {
        if (! $this->editingDocId) {
            return;
        }

        $media = $this->project->media()->findOrFail($this->editingDocId);
        $media->setCustomProperty('acquirer', $this->editAcquirer ?: 'General');
        $media->setCustomProperty('category', $this->editCategory ?: 'Other');
        $media->save();

        $this->editingDocId = null;
        $this->project->refresh();

        session()->flash('message', 'Document details updated.');
    }

    public function cancelEditing(): void
    {
        $this->editingDocId = null;
    }

    public function deleteDocument(int $id): void
    {
        $media = $this->project->media()->findOrFail($id);
        $media->delete();
        $this->project->refresh();

        session()->flash('message', 'Document successfully deleted.');
    }

    public function resetFilters(): void
    {
        $this->filterAcquirer = '';
        $this->filterCategory = '';
    }

    public function render()
    {
        $allDocuments = $this->project->getMedia('company_documents');

        $documents = $allDocuments->filter(function ($doc) {
            $matchAcquirer = true;
            $matchCategory = true;

            if (! empty($this->filterAcquirer)) {
                $docAcquirer = $doc->getCustomProperty('acquirer', 'General');
                $matchAcquirer = (strtolower($docAcquirer) === strtolower($this->filterAcquirer));
            }

            if (! empty($this->filterCategory)) {
                $docCategory = $doc->getCustomProperty('category', 'Other');
                $matchCategory = (strtolower($docCategory) === strtolower($this->filterCategory));
            }

            return $matchAcquirer && $matchCategory;
        });

        return view('livewire.projects.documents-section', [
            'documents' => $documents,
            'availableAcquirers' => $this->availableAcquirers,
            'categoriesList' => $this->categoriesList,
        ]);
    }
}
