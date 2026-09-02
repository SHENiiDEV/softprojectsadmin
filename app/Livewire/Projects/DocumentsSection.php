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
        $available = $this->availableAcquirers;
        $this->selectedAcquirer = $available[0] ?? 'General';
    }

    public function getAvailableAcquirersProperty(): array
    {
        $list = [];

        // Collect gateways from company websites
        $websites = $this->project->websites()->get();
        foreach ($websites as $website) {
            $gateways = is_array($website->gateways) ? array_filter(array_map('trim', $website->gateways)) : [];
            foreach ($gateways as $g) {
                if (! empty($g) && ! in_array($g, $list, true)) {
                    $list[] = $g;
                }
            }
        }

        // Collect from compliance boarding providers data
        if ($this->project->boarding) {
            $savedData = is_array($this->project->boarding->providers_data) ? $this->project->boarding->providers_data : [];
            foreach ($savedData as $item) {
                if (is_array($item) && ! empty($item['name']) && ! in_array($item['name'], $list, true)) {
                    $list[] = $item['name'];
                }
            }
            if (! empty($this->project->boarding->provider_name) && ! in_array($this->project->boarding->provider_name, $list, true)) {
                $list[] = $this->project->boarding->provider_name;
            }
        }

        // Fallback option if list is empty
        if (empty($list)) {
            $list[] = 'General';
        }

        $uniqueList = array_values(array_unique($list));
        natcasesort($uniqueList);

        return array_values($uniqueList);
    }

    public function getCategoriesListProperty(): array
    {
        return [
            'KYB PACK (.zip)',
            'ID/Passport',
            'CV',
            'Utility Bill',
            'Private Bank Statement',
            'Business Bank Statement',
            'Proof Of Domain',
            'Supplier Agreement & Invoice',
            'Rent Agreement & Invoice',
            'Business Plan',
            'Marketing Agreement & Invoice',
            'Settelment Account',
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
