<div class="space-y-6">
    {{-- Upload Form with Modern Drag & Dropzone --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-outfit font-bold text-base text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up text-sky-500"></i> Upload & Categorize Company Documents
            </h3>
            <span class="text-xs text-slate-400">Specify Gateway Provider & Category before saving</span>
        </div>
        
        <form wire:submit.prevent="uploadDocuments" 
              x-data="{ 
                  isDropping: false, 
                  isUploading: false, 
                  progress: 0 
              }"
              x-on:livewire-upload-start="isUploading = true; progress = 0"
              x-on:livewire-upload-finish="isUploading = false"
              x-on:livewire-upload-error="isUploading = false"
              x-on:livewire-upload-progress="progress = $event.detail.progress"
              class="space-y-5">
            
            <!-- Metadata Selectors: Acquirer / Gateway Provider & Document Category -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50/60 dark:bg-slate-950/40 p-4 rounded-xl border border-slate-200/60 dark:border-slate-800/60">
                <!-- Gateway Provider / Acquirer Selector -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-building-columns text-sky-500"></i>
                        Gateway Provider / Acquirer <span class="text-rose-500">*</span>
                    </label>
                    <select wire:model.live="selectedAcquirer" class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        @foreach($availableAcquirers as $acq)
                            <option value="{{ $acq }}">{{ $acq }}</option>
                        @endforeach
                    </select>

                    @if($selectedAcquirer === 'Other')
                        <div class="mt-2">
                            <input type="text" wire:model="customAcquirer" placeholder="Enter custom Gateway/Acquirer name..." class="w-full px-3.5 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        </div>
                    @endif
                </div>

                <!-- Document Category Selector -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-folder-tree text-indigo-500"></i>
                        Document Category <span class="text-rose-500">*</span>
                    </label>
                    <select wire:model="selectedCategory" class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        @foreach($categoriesList as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Interactive Dropzone Area -->
            <div class="relative w-full">
                <label @dragover.prevent="isDropping = true"
                       @dragleave.prevent="isDropping = false"
                       @drop.prevent="isDropping = false; if ($event.dataTransfer.files.length) { $wire.uploadMultiple('files', $event.dataTransfer.files); }"
                       :class="{ 
                           'bg-sky-50/80 dark:bg-sky-950/40 border-sky-400 dark:border-sky-500 scale-[1.01] shadow-md ring-4 ring-sky-500/10': isDropping,
                           'bg-slate-50/50 dark:bg-slate-950/20 border-slate-200 dark:border-slate-800/80 hover:bg-slate-50 dark:hover:bg-slate-950/40 hover:border-sky-300 dark:hover:border-sky-700': !isDropping
                       }"
                       class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed rounded-2xl cursor-pointer transition-all duration-200 group">
                    
                    <div class="flex flex-col items-center justify-center pt-4 pb-5 text-center px-4">
                        <div :class="{ 'scale-110 text-sky-500': isDropping, 'text-slate-400 group-hover:text-sky-500 group-hover:scale-110': !isDropping }"
                             class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 flex items-center justify-center mb-2.5 shadow-sm transition-all duration-200">
                            <i class="fa-solid fa-cloud-arrow-up text-xl"></i>
                        </div>
                        <p class="mb-1 text-xs font-bold text-slate-700 dark:text-slate-200">
                            <span class="text-sky-600 dark:text-sky-400 group-hover:underline">Click to upload</span> or drag and drop files here
                        </p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500">
                            PDF, DOCX, XLSX, PNG, JPG, ZIP (Max 100MB per file to support KYB PACKs)
                        </p>
                    </div>

                    <input type="file" 
                           wire:model="files" 
                           class="hidden" 
                           multiple />
                </label>
            </div>

            <!-- Upload Progress Bar -->
            <div x-show="isUploading" x-transition class="space-y-1.5 pt-1">
                <div class="flex items-center justify-between text-xs font-semibold text-sky-600 dark:text-sky-400">
                    <span>Uploading files...</span>
                    <span x-text="progress + '%'"></span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                    <div class="bg-sky-500 h-2 rounded-full transition-all duration-150" :style="'width: ' + progress + '%'"></div>
                </div>
            </div>

            @error('files.*')
                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
            @enderror

            <!-- Selected Files List Preview -->
            @if(count($files) > 0)
                <div class="bg-slate-50 dark:bg-slate-950/60 rounded-xl p-4 border border-slate-200/60 dark:border-slate-800 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-200/50 dark:border-slate-800/60 pb-2">
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-list-check text-sky-500"></i>
                            Selected Files ({{ count($files) }})
                        </span>
                        <span class="text-[11px] text-sky-600 dark:text-sky-400 font-semibold">
                            Target: {{ $selectedAcquirer === 'Other' ? ($customAcquirer ?: 'Other') : $selectedAcquirer }} | {{ $selectedCategory }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @foreach($files as $index => $file)
                            @php
                                $ext = strtolower($file->getClientOriginalExtension());
                                $iconClass = match($ext) {
                                    'pdf' => 'fa-file-pdf text-rose-500',
                                    'doc', 'docx' => 'fa-file-word text-blue-500',
                                    'xls', 'xlsx' => 'fa-file-excel text-emerald-500',
                                    'png', 'jpg', 'jpeg' => 'fa-file-image text-purple-500',
                                    'zip', 'rar' => 'fa-file-zipper text-amber-500',
                                    default => 'fa-file text-slate-400',
                                };
                            @endphp
                            <div class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 rounded-xl p-3 flex items-center justify-between shadow-sm">
                                <div class="flex items-center space-x-2.5 min-w-0">
                                    <i class="fa-solid {{ $iconClass }} text-lg"></i>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate" title="{{ $file->getClientOriginalName() }}">
                                            {{ $file->getClientOriginalName() }}
                                        </p>
                                        <p class="text-[10px] text-slate-400">
                                            {{ number_format($file->getSize() / 1024 / 1024, 2) }} MB
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Submit Action -->
            <div class="flex justify-end pt-2">
                <button type="submit" 
                        wire:loading.attr="disabled"
                        @if(empty($files)) disabled @endif
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-sky-600 hover:bg-sky-500 disabled:opacity-50 disabled:pointer-events-none text-white text-xs font-semibold rounded-xl transition-all duration-150 shadow-sm cursor-pointer hover:shadow-sky-500/25">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span wire:loading.remove wire:target="uploadDocuments">Save Uploaded Documents</span>
                    <span wire:loading wire:target="uploadDocuments">Saving Documents...</span>
                </button>
            </div>
        </form>
    </div>

    {{-- File List & Filters --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h3 class="font-outfit font-bold text-base text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-folder-closed text-sky-500"></i> Uploaded Company Documents
            </h3>

            <!-- Filters Bar -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Filter by Acquirer -->
                <select wire:model.live="filterAcquirer" class="px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                    <option value="">All Acquirers / Gateways</option>
                    @foreach($availableAcquirers as $acq)
                        @if($acq !== 'Other')
                            <option value="{{ $acq }}">{{ $acq }}</option>
                        @endif
                    @endforeach
                </select>

                <!-- Filter by Category -->
                <select wire:model.live="filterCategory" class="px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                    <option value="">All Categories</option>
                    @foreach($categoriesList as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>

                @if(!empty($filterAcquirer) || !empty($filterCategory))
                    <button wire:click="resetFilters" class="px-2.5 py-1.5 text-xs text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl font-semibold transition-colors">
                        <i class="fa-solid fa-xmark mr-1"></i> Clear
                    </button>
                @endif
            </div>
        </div>

        @if($documents->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-center bg-slate-50/50 dark:bg-slate-950/20 rounded-2xl border border-slate-100 dark:border-slate-800/60">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                    <i class="fa-regular fa-folder-open text-2xl"></i>
                </div>
                <p class="text-slate-500 dark:text-slate-400 text-xs font-medium">No documents matching selected filters.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50 dark:bg-slate-950/20">
                            <th class="py-3 px-4">File Name</th>
                            <th class="py-3 px-4">Gateway Provider</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Size</th>
                            <th class="py-3 px-4">Uploaded At</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-sm text-slate-700 dark:text-slate-300">
                        @foreach($documents as $doc)
                            @php
                                $ext = strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION));
                                $iconClass = match($ext) {
                                    'pdf' => 'fa-file-pdf text-rose-500',
                                    'doc', 'docx' => 'fa-file-word text-blue-500',
                                    'xls', 'xlsx' => 'fa-file-excel text-emerald-500',
                                    'png', 'jpg', 'jpeg' => 'fa-file-image text-purple-500',
                                    'zip', 'rar' => 'fa-file-zipper text-amber-500',
                                    default => 'fa-file text-slate-400',
                                };
                                $docAcquirer = $doc->getCustomProperty('acquirer', 'General');
                                $docCategory = $doc->getCustomProperty('category', 'Other');
                                $isEditing = $editingDocId === $doc->id;
                            @endphp
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors">
                                <!-- File Name -->
                                <td class="py-3.5 px-4 font-medium text-slate-900 dark:text-slate-100">
                                    <div class="flex items-center space-x-2.5">
                                        <i class="fa-solid {{ $iconClass }} text-base"></i>
                                        <span class="truncate max-w-xs font-semibold text-xs text-slate-800 dark:text-slate-200" title="{{ $doc->name }}">
                                            {{ $doc->name }}.{{ $ext }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Gateway Provider Badge / Edit Selector -->
                                <td class="py-3.5 px-4 text-xs">
                                    @if($isEditing)
                                        <select wire:model="editAcquirer" class="px-2 py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-semibold">
                                            @foreach($availableAcquirers as $acq)
                                                @if($acq !== 'Other')
                                                    <option value="{{ $acq }}">{{ $acq }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/50">
                                            <i class="fa-solid fa-building-columns text-[10px]"></i>
                                            {{ $docAcquirer }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Category Badge / Edit Selector -->
                                <td class="py-3.5 px-4 text-xs">
                                    @if($isEditing)
                                        <select wire:model="editCategory" class="px-2 py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-semibold">
                                            @foreach($categoriesList as $cat)
                                                <option value="{{ $cat }}">{{ $cat }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/50">
                                            <i class="fa-solid fa-folder text-[10px]"></i>
                                            {{ $docCategory }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Size -->
                                <td class="py-3.5 px-4 text-xs font-mono text-slate-500 dark:text-slate-400">{{ number_format($doc->size / 1024 / 1024, 2) }} MB</td>
                                
                                <!-- Uploaded At -->
                                <td class="py-3.5 px-4 text-xs text-slate-500 dark:text-slate-400">{{ $doc->created_at->format('d M Y H:i') }}</td>
                                
                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        @if($isEditing)
                                            <button wire:click="saveDocumentMetadata" class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 transition-all font-semibold text-xs" title="Save">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <button wire:click="cancelEditing" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all text-xs" title="Cancel">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        @else
                                            <button wire:click="editDocumentMetadata({{ $doc->id }})" class="p-2 rounded-lg text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-950/20 transition-all" title="Edit Metadata">
                                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                            </button>
                                            <a href="{{ $doc->getUrl() }}" target="_blank" class="p-2 rounded-lg text-slate-400 hover:text-sky-500 hover:bg-sky-50 dark:hover:bg-sky-950/20 transition-all" title="Download">
                                                <i class="fa-solid fa-download text-xs"></i>
                                            </a>
                                            <button wire:click="deleteDocument({{ $doc->id }})" wire:confirm="Are you sure you want to delete this document?" class="p-2 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/20 transition-all cursor-pointer" title="Delete">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
