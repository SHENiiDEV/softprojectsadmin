<?php

namespace App\Livewire\Projects;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotesSection extends Component
{
    public Project $project;

    public string $newNote = '';

    public ?int $editingNoteId = null;

    public string $editContent = '';

    public ?string $flashMessage = null;

    public ?string $flashType = null;

    protected function rules(): array
    {
        return [
            'newNote' => 'required|string|min:2|max:5000',
            'editContent' => 'required|string|min:2|max:5000',
        ];
    }

    public function addNote(): void
    {
        $this->validateOnly('newNote');

        ProjectNote::create([
            'project_id' => $this->project->id,
            'user_id' => Auth::id(),
            'content' => $this->newNote,
        ]);

        $this->newNote = '';
        $this->flash('Note added.', 'success');
    }

    public function startEdit(int $noteId): void
    {
        $note = ProjectNote::findOrFail($noteId);
        $this->editingNoteId = $noteId;
        $this->editContent = $note->content;
    }

    public function saveEdit(): void
    {
        $this->validateOnly('editContent');

        $note = ProjectNote::findOrFail($this->editingNoteId);
        $this->authorizeAction($note);

        $note->update(['content' => $this->editContent]);
        $this->editingNoteId = null;
        $this->editContent = '';
        $this->flash('Note updated.', 'success');
    }

    public function cancelEdit(): void
    {
        $this->editingNoteId = null;
        $this->editContent = '';
    }

    public function togglePin(int $noteId): void
    {
        $note = ProjectNote::findOrFail($noteId);
        $note->update(['pinned' => ! $note->pinned]);
    }

    public function deleteNote(int $noteId): void
    {
        $note = ProjectNote::findOrFail($noteId);
        $this->authorizeAction($note);
        $note->delete();
        $this->flash('Note deleted.', 'success');
    }

    private function authorizeAction(ProjectNote $note): void
    {
        $user = Auth::user();
        if ($note->user_id !== $user->id && ! $user->hasAnyRole(['admin', 'curator'])) {
            $this->flash('You can only edit your own notes.', 'error');

            return;
        }
    }

    public function dismissFlash(): void
    {
        $this->flashMessage = null;
        $this->flashType = null;
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->flashMessage = $message;
        $this->flashType = $type;
    }

    public function getListeners(): array
    {
        return [
            'note-added' => '$refresh',
        ];
    }

    public function render()
    {
        $notes = ProjectNote::where('project_id', $this->project->id)
            ->with('author')
            ->orderByDesc('pinned')
            ->orderByDesc('created_at')
            ->get();

        $users = User::orderBy('name')->get();
        $clients = Client::orderBy('name')->get();

        return view('livewire.projects.notes-section', compact('notes', 'users', 'clients'));
    }
}
