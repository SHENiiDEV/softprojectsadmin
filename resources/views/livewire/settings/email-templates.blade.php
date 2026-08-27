<div class="space-y-6">
    <x-slot name="header">Email Templates</x-slot>

    <!-- Page Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="font-outfit font-extrabold text-2xl text-slate-800 dark:text-white tracking-tight flex items-center gap-2.5">
                <i class="fa-solid fa-file-invoice text-sky-500"></i> Email Templates & Canned Responses
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage pre-written email templates with variable placeholders ({client_email}, {company_name}, {ticket_number}).</p>
        </div>

        <button wire:click="openModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-xl shadow-sm transition-all duration-150 cursor-pointer">
            <i class="fa-solid fa-plus"></i> Create Template
        </button>
    </div>

    <!-- Alert Message -->
    @if (session()->has('message'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-xl text-xs font-semibold text-emerald-700 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @forelse($templates as $tmpl)
            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm hover:border-sky-300 dark:hover:border-sky-700 transition-all space-y-3">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 mb-1">
                            {{ $tmpl->category }}
                        </span>
                        <h3 class="font-outfit font-bold text-base text-slate-800 dark:text-slate-100">{{ $tmpl->name }}</h3>
                    </div>
                    <div class="flex items-center space-x-1">
                        <button wire:click="openModal({{ $tmpl->id }})" class="p-2 text-slate-400 hover:text-sky-500 rounded-lg hover:bg-sky-50 dark:hover:bg-sky-950/20 transition-colors" title="Edit">
                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                        </button>
                        <button wire:click="deleteTemplate({{ $tmpl->id }})" wire:confirm="Are you sure you want to delete this template?" class="p-2 text-slate-400 hover:text-rose-500 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/20 transition-colors" title="Delete">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                    </div>
                </div>

                @if($tmpl->subject)
                    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                        <strong>Subject:</strong> {{ $tmpl->subject }}
                    </div>
                @endif

                <div class="bg-slate-50 dark:bg-slate-950/40 p-3.5 rounded-xl border border-slate-200/40 dark:border-slate-800/40 text-xs font-mono text-slate-700 dark:text-slate-300 whitespace-pre-line max-h-32 overflow-y-auto">
                    {{ $tmpl->body_text }}
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-10 text-center text-slate-400">
                <i class="fa-solid fa-folder-open text-3xl mb-2"></i>
                <p class="text-xs font-semibold">No email templates created yet. Click "Create Template" above to add one.</p>
            </div>
        @endforelse
    </div>

    <!-- Create / Edit Template Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-3">
                    <h3 class="font-outfit font-bold text-lg text-slate-800 dark:text-slate-100">
                        {{ $editingTemplateId ? 'Edit Email Template' : 'Create Email Template' }}
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form wire:submit.prevent="saveTemplate" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Template Name <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="name" placeholder="e.g. 💳 Refund Processing Confirmation" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 font-semibold">
                        @error('name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Category</label>
                            <select wire:model="category" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 font-semibold">
                                <option value="general">General Support</option>
                                <option value="refunds">Refunds & Payments</option>
                                <option value="verification">KYC / KYB Verification</option>
                                <option value="compliance">Compliance & Chargebacks</option>
                                <option value="technical">Technical Escalation</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Subject Prefix (Optional)</label>
                            <input type="text" wire:model="subject" placeholder="Re: Refund Processing Update" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Template Body Text <span class="text-rose-500">*</span></label>
                            <span class="text-[10px] text-sky-600 dark:text-sky-400 font-semibold">Placeholders: {client_email}, {company_name}, {website_url}, {ticket_number}</span>
                        </div>
                        <textarea wire:model="body_text" rows="6" placeholder="Type your template text..." class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 font-mono focus:outline-none focus:ring-2 focus:ring-sky-500/20"></textarea>
                        @error('body_text') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800/80">
                        <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-xl shadow-sm transition-all">Save Template</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
