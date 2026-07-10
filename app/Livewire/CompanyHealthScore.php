<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\Project;
use Illuminate\Support\Collection;

class CompanyHealthScore extends Component
{
    public string $search = '';
    public string $sortOrder = 'desc'; // desc | asc
    public string $viewMode = 'grid'; // grid | list
    public string $filterClient = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortOrder' => ['except' => 'desc'],
        'viewMode' => ['except' => 'grid'],
        'filterClient' => ['except' => ''],
    ];

    protected function loadCompanies(): Collection
    {
        $query = Project::with([
            'websites',
            'credentials',
            'boarding',
            'report',
            'director',
            'tasks',
        ])->notArchived();

        if (!empty($this->search)) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($this->search) . '%']);
        }

        if (!empty($this->filterClient)) {
            $query->where('client_id', $this->filterClient);
        }

        $companies = $query->get()->map(function (Project $project) {
            return (object) [
                'id'     => $project->id,
                'name'   => $project->name,
                'status' => $project->status,
                'health' => $this->computeHealth($project),
            ];
        });

        if ($this->sortOrder === 'asc') {
            return $companies->sortBy('health.score')->values();
        }

        return $companies->sortByDesc('health.score')->values();
    }

    /**
     * Compute a health score (0–100) based on completeness of data.
     */
    private function computeHealth(Project $project): array
    {
        $checks = [
            'Website'         => $project->websites->isNotEmpty(),
            'Director'        => $project->director !== null,
            'KYB'             => $project->boarding?->kyb_completed_at !== null,
            'Onboarding'      => $project->boarding?->boarding_completed_at !== null,
            'CFS'             => ($project->boarding?->cfs_verification ?? '') === 'completed',
            'Bank'            => ($project->boarding?->bank_verification ?? '') === 'completed',
            'Companies House' => ($project->boarding?->companies_house_verification ?? '') === 'completed',
            'Report'          => $project->report !== null,
            'Credentials'     => $project->credentials->isNotEmpty(),
        ];

        $passed = count(array_filter($checks));
        $total  = count($checks);
        $score  = $total > 0 ? (int) round(($passed / $total) * 100) : 0;

        $color = match (true) {
            $score >= 80 => [
                'bg'  => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-100/50 dark:border-emerald-900/30',
                'bar' => 'bg-gradient-to-r from-emerald-400 to-teal-500',
                'text' => 'text-emerald-600 dark:text-emerald-400',
            ],
            $score >= 50 => [
                'bg'  => 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-100/50 dark:border-amber-900/30',
                'bar' => 'bg-gradient-to-r from-amber-400 to-orange-500',
                'text' => 'text-amber-600 dark:text-amber-450',
            ],
            default      => [
                'bg'  => 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 border border-rose-100/50 dark:border-rose-900/30',
                'bar' => 'bg-gradient-to-r from-rose-400 to-red-500',
                'text' => 'text-rose-600 dark:text-rose-400',
            ],
        };

        return [
            'score'  => $score,
            'passed' => $passed,
            'total'  => $total,
            'checks' => $checks,
            'color'  => $color,
        ];
    }

    public function render()
    {
        $companies = $this->loadCompanies();
        $clients = \App\Models\Client::orderBy('name')->get();
        return view('livewire.company-health-score', compact('companies', 'clients'));
    }
}
