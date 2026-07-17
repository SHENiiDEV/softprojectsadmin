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

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function uploadDocuments(): void
    {
        $this->validate([
            'files.*' => 'required|file|max:10240', // 10MB max per file
        ]);

        foreach ($this->files as $file) {
            $this->project->addMedia($file)
                ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                ->toMediaCollection('company_documents');
        }

        $this->files = [];
        $this->project->refresh();

        session()->flash('message', 'Documents successfully uploaded.');
    }

    public function deleteDocument(int $id): void
    {
        $media = $this->project->media()->findOrFail($id);
        $media->delete();
        $this->project->refresh();

        session()->flash('message', 'Document successfully deleted.');
    }

    public function render()
    {
        $documents = $this->project->getMedia('company_documents');

        return view('livewire.projects.documents-section', compact('documents'));
    }
}
