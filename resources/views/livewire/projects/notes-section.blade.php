<div class="space-y-5">
    {{-- Flash --}}
    @if($flashMessage)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)"
             class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium
                    {{ $flashType === 'success' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/30' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 border border-rose-200 dark:border-rose-900/30' }}">
            <i class="fa-solid {{ $flashType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
            <span>{{ $flashMessage }}</span>
            <button wire:click="dismissFlash" class="ml-auto opacity-60 hover:opacity-100"><i class="fa-solid fa-xmark"></i></button>
        </div>
    @endif

    {{-- Add Note Form --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
        <h3 class="font-outfit font-bold text-base text-slate-800 dark:text-slate-100 mb-3 flex items-center gap-2">
            <i class="fa-solid fa-note-sticky text-amber-500"></i> Add Internal Note
        </h3>
        <x-comment-input
            wire-model="newNote"
            :users="$users->map(fn($u) => ['name' => $u->name, 'username' => $u->telegram_username ?: preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $u->name)), 'type' => 'User'])->concat($clients->map(fn($c) => ['name' => $c->name, 'username' => preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $c->name)) . '_client', 'type' => 'Client']))->values()"
            submit-action="$wire.addNote()"
            placeholder="Write a note visible only to the team... (use @username to mention)"
        />
        <div class="mt-3 flex justify-end">
            <button wire:click="addNote"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                <i class="fa-solid fa-plus"></i>
                <span wire:loading.remove wire:target="addNote">Add Note</span>
                <span wire:loading wire:target="addNote">Saving...</span>
            </button>
        </div>
    </div>

    {{-- Notes List --}}
    @if($notes->isEmpty())
        <div class="flex flex-col items-center justify-center py-12 text-center bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl shadow-sm">
            <div class="text-4xl mb-3">📋</div>
            <p class="text-slate-500 dark:text-slate-400 text-sm">No notes yet. Add the first one above.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($notes as $note)
                <div class="bg-white dark:bg-slate-900 border {{ $note->pinned ? 'border-amber-300 dark:border-amber-700/50' : 'border-slate-100 dark:border-slate-800/80' }} rounded-2xl p-4 shadow-sm group transition-all">
                    @if($editingNoteId === $note->id)
                        {{-- Edit mode --}}
                        <x-comment-input
                            wire-model="editContent"
                            :users="$users->map(fn($u) => ['name' => $u->name, 'username' => $u->telegram_username ?: preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $u->name)), 'type' => 'User'])->concat($clients->map(fn($c) => ['name' => $c->name, 'username' => preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $c->name)) . '_client', 'type' => 'Client']))->values()"
                            submit-action="$wire.saveEdit()"
                            placeholder="Edit your note..."
                            class="mb-2"
                        />
                        <div class="flex gap-2">
                            <button wire:click="saveEdit" class="px-3 py-1.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-semibold rounded-lg transition-colors">Save</button>
                            <button wire:click="cancelEdit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold rounded-lg transition-colors">Cancel</button>
                        </div>
                    @else
                        {{-- View mode --}}
                        <div class="flex items-start gap-3">
                            {{-- Author avatar --}}
                            <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gradient-to-tr from-sky-400 to-indigo-500 text-white font-bold flex items-center justify-center text-xs uppercase shadow-sm">
                                {{ substr($note->author?->name ?? '?', 0, 2) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $note->author?->name ?? 'Unknown' }}</span>
                                    @if($note->pinned)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200 dark:border-amber-900/30">
                                            <i class="fa-solid fa-thumbtack text-[8px]"></i> Pinned
                                        </span>
                                    @endif
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500 ml-auto">{{ $note->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">{!! $note->formatted_content !!}</p>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800/60 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button wire:click="togglePin({{ $note->id }})" title="{{ $note->pinned ? 'Unpin' : 'Pin note' }}"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-950/20 transition-colors text-xs flex items-center gap-1">
                                <i class="fa-solid fa-thumbtack"></i>
                                <span>{{ $note->pinned ? 'Unpin' : 'Pin' }}</span>
                            </button>
                            @if(auth()->id() === $note->user_id || auth()->user()->hasAnyRole(['admin', 'curator']))
                                <button wire:click="startEdit({{ $note->id }})" title="Edit"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-sky-500 hover:bg-sky-50 dark:hover:bg-sky-950/20 transition-colors text-xs flex items-center gap-1">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>
                                <button wire:click="deleteNote({{ $note->id }})"
                                        wire:confirm="Delete this note permanently?"
                                        title="Delete"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/20 transition-colors text-xs flex items-center gap-1">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
