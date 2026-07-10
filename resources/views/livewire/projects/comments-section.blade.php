<div class="space-y-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800/40">
        <div>
            <h3 class="font-outfit font-bold text-base text-slate-800 dark:text-white">Company General Chat</h3>
            <p class="text-xs text-slate-400 mt-1">Discuss compliance status, SMM progress, and operational reviews.</p>
        </div>
    </div>

    <!-- Flash message -->
    @if ($flashMessage)
        <div class="p-3.5 rounded-xl border flex items-center justify-between shadow-sm text-xs font-semibold
            {{ $flashType === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/20 border-emerald-100 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-950/20 border-rose-100 dark:border-rose-800/40 text-rose-800 dark:text-rose-400' }}">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="dismissFlash" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-350">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- Add comment area -->
    <div class="bg-slate-50 dark:bg-slate-950 border border-slate-200/50 dark:border-slate-800/80 rounded-2xl p-4 space-y-3">
        <x-comment-input
            wire-model="newCommentContent"
            :users="$users->map(fn($u) => ['name' => $u->name, 'username' => $u->telegram_username ?: preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $u->name)), 'type' => 'User'])->concat($clients->map(fn($c) => ['name' => $c->name, 'username' => preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $c->name)) . '_client', 'type' => 'Client']))->values()"
            submit-action="$wire.addComment()"
        />

        <div class="flex items-center justify-between">
            <!-- Private setting -->
            <label class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 cursor-pointer">
                <input type="checkbox" wire:model="newCommentIsPrivate" class="rounded dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-sky-600 focus:ring-sky-500 mr-2">
                🔒 Private (team only)
            </label>

            <button type="button" wire:click="addComment" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white font-bold rounded-xl text-xs transition-all duration-150 shadow-sm">
                Add Comment
            </button>
        </div>
    </div>

    <!-- Comments List -->
    <div class="space-y-4">
        @forelse($comments as $comment)
            <div class="bg-white dark:bg-slate-900 p-4.5 rounded-2xl border border-slate-200/20 dark:border-slate-800/60 shadow-sm relative group">
                <x-comment-bubble :comment="$comment" />

                <!-- Replies list -->
                <div class="mt-3.5 pl-4 border-l-2 border-slate-100 dark:border-slate-800/80 space-y-3.5">
                    @foreach($comment->replies as $reply)
                        <div class="relative group/reply">
                            <x-comment-bubble :comment="$reply" avatar-size="6" avatar-text-size="8px" />
                        </div>
                    @endforeach

                    <!-- Form to reply -->
                    <div x-data="{ showReplyForm: false }" class="mt-2">
                        <button type="button" @click="showReplyForm = !showReplyForm" class="text-[10px] font-bold text-sky-600 hover:text-sky-700 dark:text-sky-400 dark:hover:text-sky-300 transition-all">
                            <i class="fa-solid fa-reply mr-1"></i> Reply
                        </button>

                        <div x-show="showReplyForm" x-transition class="mt-2 space-y-2">
                            <x-comment-input
                                wire-model="replyCommentContent.{{ $comment->id }}"
                                :users="$users->map(fn($u) => ['name' => $u->name, 'username' => $u->telegram_username ?: preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $u->name)), 'type' => 'User'])->concat($clients->map(fn($c) => ['name' => $c->name, 'username' => preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $c->name)) . '_client', 'type' => 'Client']))->values()"
                                submit-action="$wire.addReply({{ $comment->id }}); showReplyForm = false"
                                :rows="2"
                                placeholder="Write a reply... (use @username to mention team members)"
                                :show-toolbar="false"
                                class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-850 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150"
                            />
                            <div class="flex justify-end">
                                <button type="button" wire:click="addReply({{ $comment->id }})" @click="showReplyForm = false" class="px-3 py-1 bg-sky-650 hover:bg-sky-750 text-white font-bold rounded-lg text-[10px] transition-all duration-150">
                                    Submit Reply
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-10 border-2 border-dashed border-slate-100 dark:border-slate-800/80 rounded-2xl text-slate-400 dark:text-slate-500">
                <i class="fa-regular fa-comments text-3xl text-slate-300 dark:text-slate-700"></i>
                <p class="text-xs font-semibold mt-3">No general comments yet</p>
                <p class="text-[10px] text-slate-400 mt-1">Start the conversation by adding a comment above.</p>
            </div>
        @endforelse
    </div>
</div>
