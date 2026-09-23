<div class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-sky-500/20">
                    <i class="fa-solid fa-shield-halved text-lg"></i>
                </div>
                <div>
                    <h1 class="font-outfit font-bold text-2xl text-slate-800 dark:text-white tracking-tight">PCI DSS & Compliance Vault</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Central repository for PCI DSS certificates, AOCs, ASV scans, SAQ questionnaires, and gateway agreements.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button wire:click="openUploadModal"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold rounded-xl shadow-sm shadow-sky-600/20 transition-all duration-150 cursor-pointer hover:scale-[1.02] active:scale-[0.98]">
                <i class="fa-solid fa-cloud-arrow-up text-sm"></i>
                <span>Upload Document</span>
            </button>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-transition.duration.300ms
             class="flex items-center justify-between p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl text-emerald-800 dark:text-emerald-300 text-xs">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                <span class="font-medium">{{ session('message') }}</span>
            </div>
            <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-200">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    {{-- KPI Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Documents -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Documents</p>
                <p class="text-2xl font-bold font-outfit text-slate-800 dark:text-white mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-folder-closed"></i>
            </div>
        </div>

        <!-- Covered Clients -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Clients Covered</p>
                <p class="text-2xl font-bold font-outfit text-slate-800 dark:text-white mt-1">{{ $stats['clients_covered'] }}</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        <!-- Expiring Soon (30d) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Expiring in 30 Days</p>
                <p class="text-2xl font-bold font-outfit text-amber-600 dark:text-amber-400 mt-1">{{ $stats['expiring_soon'] }}</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-clock"></i>
            </div>
        </div>

        <!-- Expired -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Expired Scans / Docs</p>
                <p class="text-2xl font-bold font-outfit text-rose-600 dark:text-rose-400 mt-1">{{ $stats['expired'] }}</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>
    </div>

    {{-- Filters & Search Toolbar --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
            <!-- Search Input -->
            <div class="lg:col-span-4 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search by title, file, client, notes..."
                       class="w-full pl-9 pr-8 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20" />
                @if(!empty($search))
                    <button wire:click="$set('search', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                @endif
            </div>

            <!-- Client Filter -->
            <div class="lg:col-span-3">
                <select wire:model.live="selectedClientId"
                        class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-medium text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                    <option value="">All Clients</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Document Type Filter -->
            <div class="lg:col-span-3">
                <select wire:model.live="filterType"
                        class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-medium text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                    <option value="">All Document Types</option>
                    @foreach($documentTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div class="lg:col-span-2">
                <select wire:model.live="filterStatus"
                        class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-medium text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                    <option value="all">All Statuses</option>
                    <option value="valid">Valid / Active</option>
                    <option value="expiring_soon">Expiring Soon</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
        </div>

        <!-- Secondary Toolbar: Sorting, View Mode, Clear -->
        <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100 dark:border-slate-800/80 text-xs">
            <div class="flex items-center gap-2">
                <span class="text-slate-400 font-medium">Sort by:</span>
                <select wire:model.live="sortBy" class="px-2.5 py-1 bg-transparent border border-slate-200 dark:border-slate-800 rounded-lg text-xs text-slate-700 dark:text-slate-300 focus:outline-none">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="title_asc">Title (A-Z)</option>
                    <option value="client_asc">Client Name (A-Z)</option>
                    <option value="expiry">Expiration Date</option>
                </select>

                @if(!empty($search) || !empty($selectedClientId) || !empty($filterType) || $filterStatus !== 'all' || $sortBy !== 'newest')
                    <button wire:click="resetFilters" class="ml-2 px-2.5 py-1 text-xs text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg font-semibold transition-colors">
                        <i class="fa-solid fa-xmark mr-1"></i> Clear Filters
                    </button>
                @endif
            </div>

            <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800/60 p-1 rounded-xl">
                <button wire:click="$set('viewMode', 'table')"
                        class="px-2.5 py-1 rounded-lg text-xs font-medium transition-all {{ $viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-sm font-semibold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700' }}"
                        title="Table View">
                    <i class="fa-solid fa-table-list mr-1"></i> Table
                </button>
                <button wire:click="$set('viewMode', 'grid')"
                        class="px-2.5 py-1 rounded-lg text-xs font-medium transition-all {{ $viewMode === 'grid' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-sm font-semibold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700' }}"
                        title="Grid View">
                    <i class="fa-solid fa-grip mr-1"></i> Grid
                </button>
            </div>
        </div>
    </div>

    {{-- Document Listing --}}
    @if($documents->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 px-4 bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl text-center shadow-sm">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-3.5">
                <i class="fa-solid fa-folder-open text-2xl"></i>
            </div>
            <h3 class="font-outfit font-bold text-base text-slate-800 dark:text-slate-200">No compliance documents found</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm">No files match your current filters. Try changing or resetting the filters, or upload a new document.</p>
            <button wire:click="openUploadModal" class="mt-4 px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold rounded-xl shadow-sm transition-all duration-150">
                <i class="fa-solid fa-plus mr-1"></i> Upload First Document
            </button>
        </div>
    @elseif($viewMode === 'table')
        {{-- Table View --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/60 dark:bg-slate-950/30">
                            <th class="py-3.5 px-5">Document</th>
                            <th class="py-3.5 px-4">Client / Company</th>
                            <th class="py-3.5 px-4">Category</th>
                            <th class="py-3.5 px-4">Validity</th>
                            <th class="py-3.5 px-4">Size</th>
                            <th class="py-3.5 px-4">Uploaded</th>
                            <th class="py-3.5 px-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-xs text-slate-700 dark:text-slate-300">
                        @foreach($documents as $doc)
                            @php
                                $ext = strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION));
                                $iconClass = match($ext) {
                                    'pdf' => 'fa-file-pdf text-rose-500',
                                    'doc', 'docx' => 'fa-file-word text-blue-500',
                                    'xls', 'xlsx' => 'fa-file-excel text-emerald-500',
                                    'zip', 'rar' => 'fa-file-zipper text-amber-500',
                                    'png', 'jpg', 'jpeg' => 'fa-file-image text-purple-500',
                                    default => 'fa-file-lines text-slate-400',
                                };

                                $typeBadgeClass = match($doc->document_type) {
                                    'PCI DSS' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800/60',
                                    'AOC' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60',
                                    'Scan' => 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-950/40 dark:text-cyan-300 dark:border-cyan-800/60',
                                    'SAQ' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60',
                                    'Agreement gateway' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/60',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                <!-- Document Title & File -->
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center flex-shrink-0 text-base">
                                            <i class="fa-solid {{ $iconClass }}"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-800 dark:text-slate-200 truncate" title="{{ $doc->title }}">{{ $doc->title }}</p>
                                            <p class="text-[11px] text-slate-400 truncate" title="{{ $doc->file_name }}">{{ $doc->file_name }}</p>
                                        </div>
                                    </div>
                                    @if($doc->notes)
                                        <p class="text-[10px] text-slate-400 mt-1 italic pl-12 line-clamp-1" title="{{ $doc->notes }}">{{ $doc->notes }}</p>
                                    @endif
                                </td>

                                <!-- Client / Company -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $doc->client?->name ?? '—' }}</span>
                                        @if($doc->project)
                                            <span class="text-[10px] text-slate-400 flex items-center gap-1 mt-0.5">
                                                <i class="fa-regular fa-building text-[9px]"></i> {{ $doc->project->name }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Category -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-semibold border {{ $typeBadgeClass }}">
                                        {{ $doc->display_type }}
                                    </span>
                                </td>

                                <!-- Validity -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($doc->valid_until)
                                        @if($doc->is_expired)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/60">
                                                <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> Expired {{ $doc->valid_until->format('d.m.Y') }}
                                            </span>
                                        @elseif($doc->is_expiring_soon)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60">
                                                <i class="fa-solid fa-clock text-[10px]"></i> Due {{ $doc->valid_until->format('d.m.Y') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60">
                                                <i class="fa-solid fa-check text-[10px]"></i> Until {{ $doc->valid_until->format('d.m.Y') }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-slate-400 text-[11px]">No expiry</span>
                                    @endif
                                </td>

                                <!-- File Size -->
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-500 font-mono text-[11px]">
                                    {{ $doc->formatted_file_size }}
                                </td>

                                <!-- Uploaded Info -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <p class="text-slate-600 dark:text-slate-300">{{ $doc->created_at->format('d M Y') }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $doc->uploader?->name ?? 'System' }}</p>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Download -->
                                        <button wire:click="downloadDocument({{ $doc->id }})"
                                                class="w-8 h-8 rounded-lg bg-sky-50 hover:bg-sky-100 text-sky-600 dark:bg-sky-950/50 dark:hover:bg-sky-900/60 dark:text-sky-400 flex items-center justify-center transition-colors"
                                                title="Download File">
                                            <i class="fa-solid fa-cloud-arrow-down text-xs"></i>
                                        </button>

                                        <!-- Edit -->
                                        <button wire:click="openEditModal({{ $doc->id }})"
                                                class="w-8 h-8 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 flex items-center justify-center transition-colors"
                                                title="Edit Metadata">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>

                                        <!-- Delete -->
                                        <button wire:click="confirmDelete({{ $doc->id }})"
                                                class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:hover:bg-rose-900/60 dark:text-rose-400 flex items-center justify-center transition-colors"
                                                title="Delete Document">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $documents->links() }}
            </div>
        </div>
    @else
        {{-- Grid Card View --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($documents as $doc)
                @php
                    $ext = strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION));
                    $iconClass = match($ext) {
                        'pdf' => 'fa-file-pdf text-rose-500',
                        'doc', 'docx' => 'fa-file-word text-blue-500',
                        'xls', 'xlsx' => 'fa-file-excel text-emerald-500',
                        'zip', 'rar' => 'fa-file-zipper text-amber-500',
                        'png', 'jpg', 'jpeg' => 'fa-file-image text-purple-500',
                        default => 'fa-file-lines text-slate-400',
                    };

                    $typeBadgeClass = match($doc->document_type) {
                        'PCI DSS' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800/60',
                        'AOC' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60',
                        'Scan' => 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-950/40 dark:text-cyan-300 dark:border-cyan-800/60',
                        'SAQ' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60',
                        'Agreement gateway' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/60',
                        default => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
                    };
                @endphp
                <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-5 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div>
                        <!-- Header with Category & Actions -->
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold border {{ $typeBadgeClass }}">
                                {{ $doc->display_type }}
                            </span>
                            <div class="flex items-center gap-1">
                                <button wire:click="openEditModal({{ $doc->id }})" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1" title="Edit">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </button>
                                <button wire:click="confirmDelete({{ $doc->id }})" class="text-slate-400 hover:text-rose-600 p-1" title="Delete">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Document Title & Icon -->
                        <div class="flex items-start gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center flex-shrink-0 text-lg">
                                <i class="fa-solid {{ $iconClass }}"></i>
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-semibold text-xs text-slate-800 dark:text-white line-clamp-1" title="{{ $doc->title }}">{{ $doc->title }}</h4>
                                <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ $doc->file_name }}</p>
                            </div>
                        </div>

                        <!-- Client details -->
                        <div class="bg-slate-50 dark:bg-slate-950/50 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800/80 mb-3 space-y-1">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-400">Client:</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300 truncate max-w-[150px]">{{ $doc->client?->name ?? '—' }}</span>
                            </div>
                            @if($doc->project)
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="text-slate-400">Company:</span>
                                    <span class="text-slate-600 dark:text-slate-300 truncate max-w-[150px]">{{ $doc->project->name }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Validity info -->
                        @if($doc->valid_until)
                            <div class="mb-3">
                                @if($doc->is_expired)
                                    <div class="flex items-center gap-1.5 text-[11px] font-semibold text-rose-600 dark:text-rose-400">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        <span>Expired on {{ $doc->valid_until->format('d M Y') }}</span>
                                    </div>
                                @elseif($doc->is_expiring_soon)
                                    <div class="flex items-center gap-1.5 text-[11px] font-semibold text-amber-600 dark:text-amber-400">
                                        <i class="fa-solid fa-clock"></i>
                                        <span>Expiring soon ({{ $doc->valid_until->format('d M Y') }})</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-1.5 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <span>Valid until {{ $doc->valid_until->format('d M Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($doc->notes)
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 italic line-clamp-2 mb-3 bg-slate-50/50 dark:bg-slate-950/30 p-2 rounded-lg">
                                "{{ $doc->notes }}"
                            </p>
                        @endif
                    </div>

                    <!-- Card Footer -->
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-[10px] text-slate-400 font-mono">{{ $doc->formatted_file_size }}</span>
                        <button wire:click="downloadDocument({{ $doc->id }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-600 hover:bg-sky-500 text-white text-[11px] font-semibold rounded-lg shadow-sm transition-all">
                            <i class="fa-solid fa-cloud-arrow-down"></i> Download
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $documents->links() }}
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- UPLOAD MODAL                                                              --}}
    {{-- ========================================================================= --}}
    @if($showUploadModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div wire:click="closeModals" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-slate-200 dark:border-slate-800">
                    <form wire:submit.prevent="uploadDocument"
                          x-data="{ isDropping: false, isUploading: false, progress: 0 }"
                          x-on:livewire-upload-start="isUploading = true; progress = 0"
                          x-on:livewire-upload-finish="isUploading = false"
                          x-on:livewire-upload-error="isUploading = false"
                          x-on:livewire-upload-progress="progress = $event.detail.progress">
                        
                        <div class="px-6 pt-6 pb-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 flex items-center justify-center text-sm">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                </div>
                                <h3 class="font-outfit font-bold text-base text-slate-800 dark:text-white">Upload PCI DSS & Compliance Document</h3>
                            </div>
                            <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                <i class="fa-solid fa-xmark text-sm"></i>
                            </button>
                        </div>

                        <div class="px-6 py-5 space-y-4 max-h-[75vh] overflow-y-auto text-xs">
                            <!-- Client & Optional Project -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Select Client <span class="text-rose-500">*</span>
                                    </label>
                                    <select wire:model.live="uploadClientId" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                                        <option value="">Choose a client...</option>
                                        @foreach($clients as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('uploadClientId') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Company / Project <span class="text-slate-400 font-normal">(Optional)</span>
                                    </label>
                                    <select wire:model="uploadProjectId" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/20" @if(!$uploadClientId) disabled @endif>
                                        <option value="">Entire Client Account</option>
                                        @foreach($uploadProjects as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Document Type & Custom Type -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Document Category <span class="text-rose-500">*</span>
                                    </label>
                                    <select wire:model.live="uploadType" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                                        @foreach($documentTypes as $t)
                                            <option value="{{ $t }}">{{ $t }}</option>
                                        @endforeach
                                    </select>
                                    @error('uploadType') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Valid Until / Expiration <span class="text-slate-400 font-normal">(Optional)</span>
                                    </label>
                                    <input type="date" wire:model="uploadValidUntil"
                                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/20" />
                                    @error('uploadValidUntil') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Custom type text input if "Other" -->
                            @if($uploadType === 'Other')
                                <div class="bg-indigo-50/60 dark:bg-indigo-950/30 p-3 rounded-xl border border-indigo-200 dark:border-indigo-800/60">
                                    <label class="block font-semibold text-indigo-900 dark:text-indigo-200 mb-1">
                                        Specify Custom Document Type <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" wire:model="uploadCustomType"
                                           placeholder="e.g. Penetration Test Report, Merchant Agreement Addendum..."
                                           class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-indigo-300 dark:border-indigo-700 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30" />
                                    @error('uploadCustomType') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            <!-- Document Title -->
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Document Title / Description <span class="text-slate-400 font-normal">(Optional, defaults to filename)</span>
                                </label>
                                <input type="text" wire:model="uploadTitle"
                                       placeholder="e.g. Q3 ASV Vulnerability Scan Report"
                                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/20" />
                                @error('uploadTitle') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Drag & Dropzone -->
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Select File <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative w-full">
                                    <label @dragover.prevent="isDropping = true"
                                           @dragleave.prevent="isDropping = false"
                                           @drop.prevent="isDropping = false; if ($event.dataTransfer.files.length) { $wire.upload('uploadFile', $event.dataTransfer.files[0]); }"
                                           :class="{ 
                                               'bg-sky-50 dark:bg-sky-950/40 border-sky-400 dark:border-sky-500 scale-[1.01] shadow-md': isDropping,
                                               'bg-slate-50/70 dark:bg-slate-950/30 border-slate-200 dark:border-slate-800 hover:border-sky-400': !isDropping
                                           }"
                                           class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed rounded-2xl cursor-pointer transition-all duration-150">
                                        <div class="flex flex-col items-center justify-center p-4 text-center">
                                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center mb-2 shadow-sm text-slate-500">
                                                <i class="fa-solid fa-cloud-arrow-up text-base"></i>
                                            </div>
                                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                                <span class="text-sky-600 dark:text-sky-400 underline">Click to choose file</span> or drag & drop here
                                            </p>
                                            <p class="text-[10px] text-slate-400 mt-0.5">
                                                PDF, DOCX, XLSX, ZIP, PNG, JPG (Max 100MB)
                                            </p>
                                        </div>
                                        <input type="file" wire:model="uploadFile" class="hidden" />
                                    </label>
                                </div>

                                <!-- Upload Progress -->
                                <div x-show="isUploading" x-transition class="mt-2 space-y-1">
                                    <div class="flex items-center justify-between text-[11px] font-semibold text-sky-600 dark:text-sky-400">
                                        <span>Uploading file...</span>
                                        <span x-text="progress + '%'"></span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-sky-500 h-1.5 rounded-full transition-all duration-150" :style="'width: ' + progress + '%'"></div>
                                    </div>
                                </div>

                                @if($uploadFile)
                                    <div class="mt-2 p-2 bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 rounded-xl flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2 truncate">
                                            <i class="fa-solid fa-file text-sky-500"></i>
                                            <span class="font-medium text-slate-800 dark:text-slate-200 truncate">{{ $uploadFile->getClientOriginalName() }}</span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-mono">{{ number_format($uploadFile->getSize() / 1024 / 1024, 2) }} MB</span>
                                    </div>
                                @endif
                                @error('uploadFile') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Notes & Comments <span class="text-slate-400 font-normal">(Optional)</span>
                                </label>
                                <textarea wire:model="uploadNotes" rows="2"
                                          placeholder="Additional details regarding compliance, auditor, scan frequency, etc."
                                          class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/20"></textarea>
                                @error('uploadNotes') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-950/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
                            <button type="button" wire:click="closeModals"
                                    class="px-4 py-2 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold rounded-xl shadow-sm transition-all disabled:opacity-50">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span wire:loading.remove wire:target="uploadDocument">Save Document</span>
                                <span wire:loading wire:target="uploadDocument">Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- EDIT MODAL                                                                --}}
    {{-- ========================================================================= --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div wire:click="closeModals" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-slate-200 dark:border-slate-800">
                    <form wire:submit.prevent="saveEdit">
                        <div class="px-6 pt-6 pb-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </div>
                                <h3 class="font-outfit font-bold text-base text-slate-800 dark:text-white">Edit Document Metadata</h3>
                            </div>
                            <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                <i class="fa-solid fa-xmark text-sm"></i>
                            </button>
                        </div>

                        <div class="px-6 py-5 space-y-4 max-h-[75vh] overflow-y-auto text-xs">
                            <!-- Client & Optional Project -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Client <span class="text-rose-500">*</span>
                                    </label>
                                    <select wire:model.live="editClientId" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                        @foreach($clients as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('editClientId') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Company / Project <span class="text-slate-400 font-normal">(Optional)</span>
                                    </label>
                                    <select wire:model="editProjectId" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                        <option value="">Entire Client Account</option>
                                        @foreach($editProjects as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Document Type & Expiration -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Document Category <span class="text-rose-500">*</span>
                                    </label>
                                    <select wire:model.live="editType" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                        @foreach($documentTypes as $t)
                                            <option value="{{ $t }}">{{ $t }}</option>
                                        @endforeach
                                    </select>
                                    @error('editType') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Valid Until / Expiration <span class="text-slate-400 font-normal">(Optional)</span>
                                    </label>
                                    <input type="date" wire:model="editValidUntil"
                                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                                    @error('editValidUntil') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            @if($editType === 'Other')
                                <div class="bg-indigo-50/60 dark:bg-indigo-950/30 p-3 rounded-xl border border-indigo-200 dark:border-indigo-800/60">
                                    <label class="block font-semibold text-indigo-900 dark:text-indigo-200 mb-1">
                                        Specify Custom Document Type <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" wire:model="editCustomType"
                                           placeholder="e.g. Penetration Test Report"
                                           class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-indigo-300 dark:border-indigo-700 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30" />
                                    @error('editCustomType') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            <!-- Document Title -->
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Document Title <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" wire:model="editTitle"
                                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                                @error('editTitle') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Notes & Comments <span class="text-slate-400 font-normal">(Optional)</span>
                                </label>
                                <textarea wire:model="editNotes" rows="2"
                                          class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"></textarea>
                                @error('editNotes') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-950/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
                            <button type="button" wire:click="closeModals"
                                    class="px-4 py-2 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-xl shadow-sm transition-all">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- DELETE CONFIRMATION MODAL                                                 --}}
    {{-- ========================================================================= --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div wire:click="closeModals" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 dark:border-slate-800 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center text-lg flex-shrink-0">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <h3 class="font-outfit font-bold text-base text-slate-800 dark:text-white">Delete Document</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Are you sure you want to permanently delete this document and its file?</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 mt-6">
                        <button type="button" wire:click="closeModals"
                                class="px-4 py-2 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition-colors">
                            Cancel
                        </button>
                        <button type="button" wire:click="deleteDocument"
                                class="px-5 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold rounded-xl shadow-sm transition-all">
                            Confirm Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
