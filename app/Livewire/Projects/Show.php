<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\ProjectNote;
use Livewire\Component;

class Show extends Component
{
    public Project $project;

    public bool $showNoteModal = false;

    public string $noteContent = '';

    public function mount(Project $project): void
    {
        $this->project = $project->load(['director.manager', 'manager', 'websites']);
    }

    public function openNoteModal(): void
    {
        $this->noteContent = '';
        $this->showNoteModal = true;
        $this->resetErrorBag();
    }

    public function saveNote(): void
    {
        $this->validate([
            'noteContent' => 'required|string|min:2|max:5000',
        ]);

        ProjectNote::create([
            'project_id' => $this->project->id,
            'user_id' => auth()->id(),
            'content' => $this->noteContent,
        ]);

        $this->showNoteModal = false;
        $this->noteContent = '';

        // Dispatch to NotesSection to refresh the list of notes
        $this->dispatch('note-added')->to('projects.notes-section');

        session()->flash('message', 'Note successfully added.');
    }

    public function render()
    {
        return view('livewire.projects.show');
    }
}
