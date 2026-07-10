<?php

namespace App\Livewire\Projects;

use App\Models\Credential;
use App\Models\Project;
use App\Models\Website;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CredentialsSection extends Component
{
    public Project $project;

    public bool $showForm = false;

    public ?int $editingId = null;

    // Form fields
    public string $type = 'web_2_0';

    public string $provider_url = '';

    public ?int $website_id = null;

    public string $website_name = '';

    public string $website_url = '';

    public string $login = '';

    public string $password = '';

    public string $comments = '';

    protected function rules(): array
    {
        return [
            'type' => 'required|string',
            'provider_url' => 'nullable|url',
            'website_id' => [
                'nullable',
                'exists:websites,id',
                Rule::exists('websites', 'id')->where('project_id', $this->project->id),
            ],
            'website_name' => 'nullable|string|max:255',
            'website_url' => 'nullable|url',
            'login' => 'required|string|max:255',
            'password' => 'required|string',
            'comments' => 'nullable|string',
        ];
    }

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->resetForm();
        $credential = Credential::findOrFail($id);
        $this->editingId = $credential->id;
        $this->type = $credential->type;
        $this->provider_url = $credential->provider_url ?? '';
        $this->website_id = $credential->website_id;
        $this->website_name = $credential->website ? $credential->website->name : '';
        $this->website_url = $credential->website ? $credential->website->url : '';
        $this->login = $credential->login;
        $this->password = $credential->password; // Automatically decrypted by Laravel cast
        $this->comments = $credential->comments ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $credential = Credential::findOrFail($this->editingId);
            $credential->update([
                'type' => $this->type,
                'provider_url' => $this->provider_url ?: null,
                'website_id' => $this->website_id ?: null,
                'login' => $this->login,
                'password' => $this->password, // Automatically encrypted by Laravel cast
                'comments' => $this->comments ?: null,
            ]);
            // Update linked website if provided
            if ($this->website_id) {
                $website = Website::find($this->website_id);
                if ($website) {
                    $website->update([
                        'name' => $this->website_name ?: $website->name,
                        'url' => $this->website_url ?: $website->url,
                    ]);
                }
            }
            session()->flash('message', 'Credential successfully updated.');
        } else {
            $newCredential = $this->project->credentials()->create([
                'type' => $this->type,
                'provider_url' => $this->provider_url ?: null,
                'website_id' => $this->website_id ?: null,
                'login' => $this->login,
                'password' => $this->password, // Automatically encrypted by Laravel cast
                'comments' => $this->comments ?: null,
            ]);
            // If website selected, also update/create website details
            if ($this->website_id) {
                $website = Website::find($this->website_id);
                if ($website) {
                    $website->update([
                        'name' => $this->website_name ?: $website->name,
                        'url' => $this->website_url ?: $website->url,
                    ]);
                }
            }
            session()->flash('message', 'New credential successfully added.');
        }

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $credential = Credential::findOrFail($id);
        $credential->delete();
        session()->flash('message', 'Credential successfully deleted.');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->type = 'web_2_0';
        $this->provider_url = '';
        $this->website_id = null;
        $this->website_name = '';
        $this->website_url = '';
        $this->login = '';
        $this->password = '';
        $this->comments = '';
        $this->resetValidation();
    }

    public function render()
    {
        $credentials = $this->project->credentials()->with('website')->orderBy('id', 'desc')->get();

        return view('livewire.projects.credentials-section', [
            'credentials' => $credentials,
        ]);
    }
}
