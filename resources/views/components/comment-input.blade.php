@props([
    'wireModel' => 'newCommentContent',
    'users' => '[]',
    'submitAction' => '$wire.addComment()',
    'rows' => 3,
    'placeholder' => 'Write a comment... (use @username to mention team members)',
    'showToolbar' => true,
])

<div x-data="{
    showMentions: false,
    searchMention: '',
    users: {{ $users instanceof \Illuminate\Support\Collection ? $users->toJson() : $users }},
    activeIndex: 0,
    textarea: null,

    get filteredUsers() {
        if (!this.searchMention) return this.users;
        return this.users.filter(u =>
            u.username.toLowerCase().includes(this.searchMention.toLowerCase()) ||
            u.name.toLowerCase().includes(this.searchMention.toLowerCase())
        );
    },

    onInput(e) {
        this.textarea = e.target;
        const text = this.textarea.value;
        const selectionEnd = this.textarea.selectionEnd;

        const textBeforeCursor = text.substring(0, selectionEnd);
        const lastAtIdx = textBeforeCursor.lastIndexOf('@');

        if (lastAtIdx !== -1 && (lastAtIdx === 0 || /\s/.test(textBeforeCursor.charAt(lastAtIdx - 1)))) {
            const potentialMention = textBeforeCursor.substring(lastAtIdx + 1);
            if (!/\s/.test(potentialMention)) {
                this.showMentions = true;
                this.searchMention = potentialMention;
                this.activeIndex = 0;
                return;
            }
        }

        this.showMentions = false;
        this.searchMention = '';
    },

    selectUser(username) {
        if (!this.textarea) return;
        const text = this.textarea.value;
        const selectionEnd = this.textarea.selectionEnd;
        const textBeforeCursor = text.substring(0, selectionEnd);
        const lastAtIdx = textBeforeCursor.lastIndexOf('@');

        const before = text.substring(0, lastAtIdx);
        const after = text.substring(selectionEnd);

        const replacement = '@' + username + ' ';
        this.textarea.value = before + replacement + after;

        this.textarea.dispatchEvent(new Event('input'));

        const newCursorPos = lastAtIdx + replacement.length;
        this.textarea.setSelectionRange(newCursorPos, newCursorPos);
        this.textarea.focus();

        this.showMentions = false;
        this.searchMention = '';
    },

    onKeyDown(e) {
        if (!this.showMentions) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this.activeIndex = (this.activeIndex + 1) % this.filteredUsers.length;
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            this.activeIndex = (this.activeIndex - 1 + this.filteredUsers.length) % this.filteredUsers.length;
        } else if (e.key === 'Enter') {
            if (this.filteredUsers.length > 0) {
                e.preventDefault();
                this.selectUser(this.filteredUsers[this.activeIndex].username);
            }
        } else if (e.key === 'Escape') {
            e.preventDefault();
            this.showMentions = false;
        }
    },

    wrap(before, after) {
        if (!this.textarea) this.textarea = this.$refs.commentBox;
        const ta = this.textarea;
        const start = ta.selectionStart;
        const end = ta.selectionEnd;
        const selected = ta.value.substring(start, end);
        const replacement = before + (selected || 'text') + after;
        ta.setRangeText(replacement, start, end, 'select');
        ta.dispatchEvent(new Event('input'));
        ta.focus();
    }
}" class="relative">
    @if($showToolbar)
        <!-- Formatting Toolbar -->
        <div class="flex items-center space-x-1 mb-1.5">
            <button type="button" @click="wrap('**', '**')" class="px-2 py-1 rounded-lg text-[10px] font-bold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200 transition-all" title="Bold">
                <i class="fa-solid fa-bold"></i>
            </button>
            <button type="button" @click="wrap('*', '*')" class="px-2 py-1 rounded-lg text-[10px] font-bold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200 transition-all" title="Italic">
                <i class="fa-solid fa-italic"></i>
            </button>
            <button type="button" @click="wrap('`', '`')" class="px-2 py-1 rounded-lg text-[10px] font-bold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200 transition-all" title="Code">
                <i class="fa-solid fa-code"></i>
            </button>
            <span class="text-[9px] text-slate-300 dark:text-slate-600 pl-1">|</span>
            <span class="text-[9px] text-slate-400 dark:text-slate-500 pl-1">Use @mention, **bold**, *italic*, `code`</span>
        </div>
    @endif

    <textarea wire:model="{{ $wireModel }}"
              x-ref="commentBox"
              @input="onInput"
              @keydown="onKeyDown"
              @keydown.enter.prevent="if (!showMentions && !$event.shiftKey) { {{ $submitAction }} }"
              rows="{{ $rows }}"
              placeholder="{{ $placeholder }}"
              {{ $attributes->merge(['class' => 'w-full px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150']) }}></textarea>

    <!-- Mentions Dropdown -->
    <div x-show="showMentions && filteredUsers.length > 0"
         x-transition
         class="absolute bottom-full mb-1 left-0 z-50 w-64 bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 rounded-xl shadow-lg max-h-48 overflow-y-auto p-1.5 space-y-0.5"
         style="display: none;">
        <template x-for="(u, index) in filteredUsers" :key="u.username">
            <button type="button"
                    @click="selectUser(u.username)"
                    :class="index === activeIndex ? 'bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-850/80'"
                    class="w-full text-left px-3 py-1.5 rounded-lg text-xs transition-colors flex items-center justify-between">
                <span class="flex items-center gap-1.5">
                    <span x-text="u.name"></span>
                    <span x-show="u.type === 'Client'" class="px-1.5 py-0.2 text-[8px] font-bold uppercase tracking-wider rounded bg-indigo-50 dark:bg-indigo-950/40 text-indigo-650 dark:text-indigo-400 border border-indigo-150/40 dark:border-indigo-900/30">Client</span>
                </span>
                <span class="text-[10px] text-slate-400 dark:text-slate-500" x-text="'@' + u.username"></span>
            </button>
        </template>
    </div>

    @error($wireModel) <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
</div>
