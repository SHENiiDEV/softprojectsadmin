<div class="space-y-6">
    {{-- Upload Form --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm">
        <h3 class="font-outfit font-bold text-base text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-cloud-arrow-up text-sky-500"></i> Upload Company Documents
        </h3>
        
        <form wire:submit.prevent="upload" class="space-y-4">
            <div class="flex items-center justify-center w-full">
                <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-200 dark:border-slate-800 border-dashed rounded-2xl cursor-pointer bg-slate-50/50 dark:bg-slate-950/20 hover:bg-slate-50 dark:hover:bg-slate-950/40 transition-colors">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <i class="fa-solid fa-folder-open text-2xl text-slate-400 mb-2"></i>
                        <p class="mb-1 text-xs text-slate-500 dark:text-slate-400 font-semibold">Click to select files</p>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500">Max size per file: 10MB</p>
                    </div>
                    <input type="file" wire:model="files" class="hidden" multiple />
                </label>
            </div>
            
            @error('files.*')
                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
            @enderror

            @if(count($files) > 0)
                <div class="bg-slate-50 dark:bg-slate-950/50 rounded-xl p-3.5 border border-slate-100 dark:border-slate-800/60">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Selected Files</span>
                    <ul class="space-y-1.5">
                        @foreach($files as $index => $file)
                            <li class="text-xs text-slate-600 dark:text-slate-400 flex items-center justify-between">
                                <span class="truncate pr-4"><i class="fa-regular fa-file mr-1 text-slate-400"></i>{{ $file->getClientOriginalName() }}</span>
                                <span class="text-[10px] text-slate-400">{{ number_format($file->getSize() / 1024 / 1024, 2) }} MB</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex justify-end pt-2">
                <button type="submit" 
                        wire:loading.attr="disabled"
                        @if(empty($files)) disabled @endif
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-700 disabled:opacity-50 disabled:pointer-events-none text-white text-sm font-semibold rounded-xl transition-colors shadow-sm cursor-pointer">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span wire:loading.remove wire:target="upload">Upload Documents</span>
                    <span wire:loading wire:target="upload">Uploading...</span>
                </button>
            </div>
        </form>
    </div>

    {{-- File List --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm">
        <h3 class="font-outfit font-bold text-base text-slate-800 dark:text-slate-100 mb-4">Uploaded Documents</h3>

        @if($documents->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-center bg-slate-50/50 dark:bg-slate-950/20 rounded-2xl border border-slate-100 dark:border-slate-800/60">
                <div class="text-4xl mb-3 text-slate-300"><i class="fa-regular fa-file-pdf"></i></div>
                <p class="text-slate-500 dark:text-slate-400 text-sm">No documents uploaded for this company yet.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-3 px-4">File Name</th>
                            <th class="py-3 px-4">Size</th>
                            <th class="py-3 px-4">Uploaded At</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-sm text-slate-700 dark:text-slate-300">
                        @foreach($documents as $doc)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors">
                                <td class="py-3.5 px-4 font-medium text-slate-900 dark:text-slate-100">
                                    <div class="flex items-center space-x-2.5">
                                        <i class="fa-solid fa-file text-slate-400 text-base"></i>
                                        <span class="truncate max-w-xs md:max-w-md" title="{{ $doc->name }}">{{ $doc->name }}.{{ pathinfo($doc->file_name, PATHINFO_EXTENSION) }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-xs font-mono">{{ number_format($doc->size / 1024 / 1024, 2) }} MB</td>
                                <td class="py-3.5 px-4 text-xs text-slate-500 dark:text-slate-400">{{ $doc->created_at->format('d M Y H:i') }}</td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ $doc->getUrl() }}" target="_blank" class="p-2 rounded-lg text-slate-400 hover:text-sky-500 hover:bg-sky-50 dark:hover:bg-sky-950/20 transition-all" title="Download">
                                            <i class="fa-solid fa-download text-sm"></i>
                                        </a>
                                        <button wire:click="deleteDocument({{ $doc->id }})" wire:confirm="Are you sure you want to delete this document?" class="p-2 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/20 transition-all cursor-pointer" title="Delete">
                                            <i class="fa-solid fa-trash text-sm"></i>
                                        </button>
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
