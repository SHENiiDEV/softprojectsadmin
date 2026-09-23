<?php

namespace App\Livewire\PciDss;

use App\Models\Client;
use App\Models\PciDssDocument;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    // Navigation & Filters
    public ?int $selectedClientId = null;

    public string $filterType = '';

    public string $search = '';

    public string $filterStatus = 'all'; // all | valid | expiring_soon | expired

    public string $sortBy = 'newest'; // newest | oldest | title_asc | client_asc | expiry

    public string $viewMode = 'table'; // table | grid

    public string $rootView = 'folders'; // folders | flat

    // Upload Modal State & Fields
    public bool $showUploadModal = false;

    public ?int $uploadClientId = null;

    public ?int $uploadProjectId = null;

    public string $uploadType = 'PCI DSS';

    public string $uploadCustomType = '';

    public string $uploadTitle = '';

    public $uploadFile = null;

    public ?string $uploadValidUntil = null;

    public string $uploadNotes = '';

    // Edit Modal State & Fields
    public bool $showEditModal = false;

    public ?int $editingDocId = null;

    public ?int $editClientId = null;

    public ?int $editProjectId = null;

    public string $editType = 'PCI DSS';

    public string $editCustomType = '';

    public string $editTitle = '';

    public ?string $editValidUntil = null;

    public string $editNotes = '';

    // Delete Modal State
    public bool $showDeleteModal = false;

    public ?int $deletingDocId = null;

    /**
     * @var array<string, array<string, string>>
     */
    protected $queryString = [
        'selectedClientId' => ['except' => null, 'as' => 'client'],
        'filterType' => ['except' => '', 'as' => 'type'],
        'search' => ['except' => ''],
        'filterStatus' => ['except' => 'all', 'as' => 'status'],
        'sortBy' => ['except' => 'newest'],
        'viewMode' => ['except' => 'table'],
        'rootView' => ['except' => 'folders'],
    ];

    public function mount(?int $client = null): void
    {
        if ($client && Client::where('id', $client)->exists()) {
            $this->selectedClientId = $client;
        }
    }

    public function openFolder(int $clientId): void
    {
        $this->selectedClientId = $clientId;
        $this->filterType = '';
        $this->resetPage();
    }

    public function closeFolder(): void
    {
        $this->selectedClientId = null;
        $this->filterType = '';
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedClientId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function openUploadModal(?int $clientId = null): void
    {
        $this->resetValidation();
        $this->uploadFile = null;
        $this->uploadTitle = '';

        if (! empty($this->filterType) && in_array($this->filterType, PciDssDocument::DOCUMENT_TYPES, true)) {
            $this->uploadType = $this->filterType;
        } else {
            $this->uploadType = 'PCI DSS';
        }

        $this->uploadCustomType = '';
        $this->uploadValidUntil = null;
        $this->uploadNotes = '';

        $resolvedClientId = $clientId ?? $this->selectedClientId ?? Client::orderBy('name')->value('id');
        $this->uploadClientId = $resolvedClientId;
        $this->uploadProjectId = null;

        $this->showUploadModal = true;
    }

    public function updatedUploadClientId(?int $clientId): void
    {
        $this->uploadProjectId = null;
    }

    public function updatedUploadType(string $value): void
    {
        if ($value !== 'Other') {
            $this->uploadCustomType = '';
        }
    }

    public function uploadDocument(): void
    {
        $this->validate([
            'uploadClientId' => 'required|exists:clients,id',
            'uploadProjectId' => 'nullable|exists:projects,id',
            'uploadType' => 'required|string|in:'.implode(',', PciDssDocument::DOCUMENT_TYPES),
            'uploadCustomType' => 'required_if:uploadType,Other|nullable|string|max:100',
            'uploadTitle' => 'nullable|string|max:255',
            'uploadFile' => 'required|file|max:102400', // 100MB
            'uploadValidUntil' => 'nullable|date',
            'uploadNotes' => 'nullable|string|max:2000',
        ], [
            'uploadClientId.required' => 'Please select a client.',
            'uploadCustomType.required_if' => 'Please specify the custom document type.',
            'uploadFile.required' => 'Please select a file to upload.',
            'uploadFile.max' => 'The file size must not exceed 100MB.',
        ]);

        $originalName = $this->uploadFile->getClientOriginalName();
        $fileSize = $this->uploadFile->getSize();
        $mimeType = $this->uploadFile->getMimeType();

        $storedPath = $this->uploadFile->store('pci_dss_documents', 'local');

        $title = ! empty(trim($this->uploadTitle))
            ? trim($this->uploadTitle)
            : pathinfo($originalName, PATHINFO_FILENAME);

        PciDssDocument::create([
            'client_id' => $this->uploadClientId,
            'project_id' => $this->uploadProjectId ?: null,
            'document_type' => $this->uploadType,
            'custom_type' => $this->uploadType === 'Other' ? trim($this->uploadCustomType) : null,
            'title' => $title,
            'file_path' => $storedPath,
            'file_name' => $originalName,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'valid_until' => $this->uploadValidUntil ?: null,
            'notes' => ! empty(trim($this->uploadNotes)) ? trim($this->uploadNotes) : null,
            'uploaded_by' => auth()->id(),
        ]);

        $this->showUploadModal = false;
        $this->uploadFile = null;
        $this->uploadTitle = '';
        $this->uploadNotes = '';
        $this->uploadValidUntil = null;
        $this->uploadCustomType = '';

        session()->flash('message', 'Document successfully uploaded.');
    }

    public function openEditModal(int $id): void
    {
        $this->resetValidation();
        $doc = PciDssDocument::findOrFail($id);

        $this->editingDocId = $doc->id;
        $this->editClientId = $doc->client_id;
        $this->editProjectId = $doc->project_id;
        $this->editType = $doc->document_type === 'Scan' ? 'ASV' : $doc->document_type;
        $this->editCustomType = $doc->custom_type ?? '';
        $this->editTitle = $doc->title;
        $this->editValidUntil = $doc->valid_until ? $doc->valid_until->format('Y-m-d') : null;
        $this->editNotes = $doc->notes ?? '';

        $this->showEditModal = true;
    }

    public function updatedEditClientId(?int $clientId): void
    {
        $this->editProjectId = null;
    }

    public function updatedEditType(string $value): void
    {
        if ($value !== 'Other') {
            $this->editCustomType = '';
        }
    }

    public function saveEdit(): void
    {
        if (! $this->editingDocId) {
            return;
        }

        $this->validate([
            'editClientId' => 'required|exists:clients,id',
            'editProjectId' => 'nullable|exists:projects,id',
            'editType' => 'required|string|in:'.implode(',', PciDssDocument::DOCUMENT_TYPES),
            'editCustomType' => 'required_if:editType,Other|nullable|string|max:100',
            'editTitle' => 'required|string|max:255',
            'editValidUntil' => 'nullable|date',
            'editNotes' => 'nullable|string|max:2000',
        ], [
            'editCustomType.required_if' => 'Please specify the custom document type.',
        ]);

        $doc = PciDssDocument::findOrFail($this->editingDocId);

        $doc->update([
            'client_id' => $this->editClientId,
            'project_id' => $this->editProjectId ?: null,
            'document_type' => $this->editType,
            'custom_type' => $this->editType === 'Other' ? trim($this->editCustomType) : null,
            'title' => trim($this->editTitle),
            'valid_until' => $this->editValidUntil ?: null,
            'notes' => ! empty(trim($this->editNotes)) ? trim($this->editNotes) : null,
        ]);

        $this->showEditModal = false;
        $this->editingDocId = null;

        session()->flash('message', 'Document details updated.');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingDocId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteDocument(): void
    {
        if (! $this->deletingDocId) {
            return;
        }

        $doc = PciDssDocument::findOrFail($this->deletingDocId);

        if (Storage::disk('local')->exists($doc->file_path)) {
            Storage::disk('local')->delete($doc->file_path);
        }

        $doc->delete();

        $this->showDeleteModal = false;
        $this->deletingDocId = null;

        session()->flash('message', 'Document removed successfully.');
    }

    public function downloadDocument(int $id): StreamedResponse
    {
        $doc = PciDssDocument::findOrFail($id);

        if (! Storage::disk('local')->exists($doc->file_path)) {
            abort(404, 'The requested document file does not exist on disk.');
        }

        return Storage::disk('local')->download($doc->file_path, $doc->file_name);
    }

    public function resetFilters(): void
    {
        $this->selectedClientId = null;
        $this->filterType = '';
        $this->search = '';
        $this->filterStatus = 'all';
        $this->sortBy = 'newest';
        $this->resetPage();
    }

    public function closeModals(): void
    {
        $this->showUploadModal = false;
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->editingDocId = null;
        $this->deletingDocId = null;
        $this->uploadFile = null;
    }

    public function render(): View
    {
        $clients = Client::has('pciDocuments')->orderBy('name')->get(['id', 'name']);
        $allClients = Client::orderBy('name')->get(['id', 'name']);

        $uploadProjects = $this->uploadClientId
            ? Project::where('client_id', $this->uploadClientId)->orderBy('name')->get(['id', 'name'])
            : collect();

        $editProjects = $this->editClientId
            ? Project::where('client_id', $this->editClientId)->orderBy('name')->get(['id', 'name'])
            : collect();

        $today = Carbon::today();
        $expiringThreshold = Carbon::today()->addDays(30);

        // Overall statistics
        $stats = [
            'total' => PciDssDocument::count(),
            'clients_covered' => PciDssDocument::distinct('client_id')->count('client_id'),
            'expiring_soon' => PciDssDocument::whereNotNull('valid_until')
                ->where('valid_until', '>=', $today)
                ->where('valid_until', '<=', $expiringThreshold)
                ->count(),
            'expired' => PciDssDocument::whereNotNull('valid_until')
                ->where('valid_until', '<', $today)
                ->count(),
        ];

        // Active client if selected
        $selectedClient = $this->selectedClientId
            ? Client::with('companies')->find($this->selectedClientId)
            : null;

        // Category breakdown for the active client
        $clientCategoryCounts = [];
        if ($this->selectedClientId) {
            $rawCounts = PciDssDocument::where('client_id', $this->selectedClientId)
                ->select('document_type', DB::raw('count(*) as count'))
                ->groupBy('document_type')
                ->pluck('count', 'document_type')
                ->toArray();

            foreach ($rawCounts as $type => $count) {
                $normalizedType = $type === 'Scan' ? 'ASV' : $type;
                $clientCategoryCounts[$normalizedType] = ($clientCategoryCounts[$normalizedType] ?? 0) + $count;
            }
        }

        // Folders list when viewing all clients - ONLY clients with documents
        $clientFolders = collect();
        $recentDocuments = collect();

        if (! $this->selectedClientId && $this->rootView === 'folders') {
            $clientFolders = Client::query()
                ->has('pciDocuments')
                ->withCount('pciDocuments')
                ->with(['pciDocuments' => function ($q) {
                    $q->select('id', 'client_id', 'document_type', 'custom_type', 'valid_until', 'created_at');
                }])
                ->when(! empty(trim($this->search)), function ($q) {
                    $term = '%'.trim($this->search).'%';
                    $q->where(function ($sub) use ($term) {
                        $sub->where('name', 'like', $term)
                            ->orWhereHas('pciDocuments', function ($dq) use ($term) {
                                $dq->where('title', 'like', $term)
                                    ->orWhere('file_name', 'like', $term)
                                    ->orWhere('custom_type', 'like', $term);
                            });
                    });
                })
                ->orderBy('name')
                ->get()
                ->map(function ($client) use ($today, $expiringThreshold) {
                    $docs = $client->pciDocuments;
                    $types = $docs->map(fn ($d) => $d->display_type)->unique()->values()->all();
                    $hasExpired = $docs->contains(fn ($d) => $d->valid_until && $d->valid_until->isPast() && ! $d->valid_until->isToday());
                    $hasExpiring = $docs->contains(fn ($d) => $d->valid_until && $d->valid_until->greaterThanOrEqualTo($today) && $d->valid_until->lessThanOrEqualTo($expiringThreshold));

                    return [
                        'id' => $client->id,
                        'name' => $client->name,
                        'count' => $client->pci_documents_count,
                        'types' => $types,
                        'has_expired' => $hasExpired,
                        'has_expiring_soon' => $hasExpiring,
                        'latest_upload' => $docs->max('created_at'),
                    ];
                });

            $recentDocuments = PciDssDocument::with(['client', 'project', 'uploader'])
                ->latest()
                ->take(6)
                ->get();
        }

        // Filtered documents query
        $query = PciDssDocument::query()
            ->with(['client', 'project', 'uploader']);

        if ($this->selectedClientId) {
            $query->where('client_id', $this->selectedClientId);
        }

        if (! empty($this->filterType)) {
            if ($this->filterType === 'ASV') {
                $query->whereIn('document_type', ['ASV', 'Scan']);
            } else {
                $query->where('document_type', $this->filterType);
            }
        }

        if ($this->filterStatus === 'valid') {
            $query->where(function (Builder $q) use ($today) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $today);
            });
        } elseif ($this->filterStatus === 'expiring_soon') {
            $query->whereNotNull('valid_until')
                ->where('valid_until', '>=', $today)
                ->where('valid_until', '<=', $expiringThreshold);
        } elseif ($this->filterStatus === 'expired') {
            $query->whereNotNull('valid_until')
                ->where('valid_until', '<', $today);
        }

        if (! empty(trim($this->search))) {
            $term = '%'.trim($this->search).'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('file_name', 'like', $term)
                    ->orWhere('custom_type', 'like', $term)
                    ->orWhere('notes', 'like', $term)
                    ->orWhereHas('client', function (Builder $cq) use ($term) {
                        $cq->where('name', 'like', $term);
                    })
                    ->orWhereHas('project', function (Builder $pq) use ($term) {
                        $pq->where('name', 'like', $term);
                    });
            });
        }

        match ($this->sortBy) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'title_asc' => $query->orderBy('title', 'asc'),
            'client_asc' => $query->join('clients', 'clients.id', '=', 'pci_dss_documents.client_id')
                ->orderBy('clients.name', 'asc')
                ->select('pci_dss_documents.*'),
            'expiry' => $query->orderByRaw('CASE WHEN valid_until IS NULL THEN 1 ELSE 0 END, valid_until ASC'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $documents = $query->paginate(15);

        return view('livewire.pci-dss.index', [
            'documents' => $documents,
            'clients' => $clients,
            'allClients' => $allClients,
            'selectedClient' => $selectedClient,
            'clientCategoryCounts' => $clientCategoryCounts,
            'clientFolders' => $clientFolders,
            'recentDocuments' => $recentDocuments,
            'uploadProjects' => $uploadProjects,
            'editProjects' => $editProjects,
            'stats' => $stats,
            'documentTypes' => PciDssDocument::DOCUMENT_TYPES,
        ])->layout('layouts.app');
    }
}
