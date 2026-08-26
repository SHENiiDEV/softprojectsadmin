<?php

namespace App\Livewire\Companies;

use App\Models\Company;
use App\Models\Website;
use Livewire\Component;

class Create extends Component
{
    public $name = '';

    public $onboarding_completed = false;

    public $website_id = null;

    public $websites = [];

    public function mount()
    {
        $this->websites = Website::orderBy('name')->pluck('name', 'id')->toArray();
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'onboarding_completed' => 'boolean',
            'website_id' => 'nullable|exists:websites,id',
        ];
    }

    public function submit()
    {
        $this->validate();
        Company::create([
            'name' => $this->name,
            'onboarding_completed' => $this->onboarding_completed,
            'website_id' => $this->website_id,
        ]);

        return redirect()->route('companies.index');
    }

    public function render()
    {
        return view('livewire.companies.create');
    }
}
