<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\RequiresPermission;
use App\Models\EmailTemplate;
use Livewire\Component;

class EmailTemplates extends Component
{
    use RequiresPermission;

    public $templates;

    public bool $showModal = false;

    public ?int $editingTemplateId = null;

    public string $name = '';

    public string $category = 'general';

    public string $subject = '';

    public string $body_text = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'category' => 'required|string',
        'subject' => 'nullable|string|max:255',
        'body_text' => 'required|string',
    ];

    public function mount(): void
    {
        $this->requirePermission('view_tasks');

        $this->loadTemplates();
    }

    public function loadTemplates(): void
    {
        $this->templates = EmailTemplate::with('creator')->orderBy('name')->get();
    }

    public function openModal(?int $id = null): void
    {
        $this->requirePermission('manage_tasks');

        $this->resetValidation();

        if ($id) {
            $tmpl = EmailTemplate::findOrFail($id);
            $this->editingTemplateId = $tmpl->id;
            $this->name = $tmpl->name;
            $this->category = $tmpl->category;
            $this->subject = $tmpl->subject ?: '';
            $this->body_text = $tmpl->body_text;
        } else {
            $this->editingTemplateId = null;
            $this->name = '';
            $this->category = 'general';
            $this->subject = '';
            $this->body_text = '';
        }

        $this->showModal = true;
    }

    public function saveTemplate(): void
    {
        $this->requirePermission('manage_tasks');

        $this->validate();

        EmailTemplate::updateOrCreate(
            ['id' => $this->editingTemplateId],
            [
                'name' => $this->name,
                'category' => $this->category,
                'subject' => $this->subject ?: null,
                'body_text' => $this->body_text,
                'created_by' => auth()->id(),
            ]
        );

        $this->showModal = false;
        $this->loadTemplates();
        session()->flash('message', 'Email template saved successfully.');
    }

    public function deleteTemplate(int $id): void
    {
        $this->requirePermission('manage_tasks');

        EmailTemplate::findOrFail($id)->delete();
        $this->loadTemplates();
        session()->flash('message', 'Email template deleted successfully.');
    }

    public function render()
    {
        return view('livewire.settings.email-templates');
    }
}
