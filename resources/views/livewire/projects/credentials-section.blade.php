<div
    class="space-y-6"
    x-data="{
        selectedCredential: null,
        visibleSecrets: {},
        copiedKey: null,
        credentialTypeStyles: {
            hosting: 'bg-purple-50 text-purple-700 border-purple-100 dark:bg-purple-950/30 dark:text-purple-400 dark:border-purple-900/40',
            wordpress_admin: 'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900/40',
            mail_admin: 'bg-sky-50 text-sky-700 border-sky-100 dark:bg-sky-950/30 dark:text-sky-400 dark:border-sky-900/40',
            web_2_0: 'bg-indigo-50 text-indigo-700 border-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-400 dark:border-indigo-900/40',
            other: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'
        },
        openCredential(credential) {
            this.selectedCredential = credential;
            this.visibleSecrets = {};
            this.copiedKey = null;
        },
        closeCredential() {
            this.selectedCredential = null;
            this.visibleSecrets = {};
            this.copiedKey = null;
        },
        copyField(key, value) {
            if (!value) return;
            navigator.clipboard.writeText(value);
            this.copiedKey = key;
            setTimeout(() => {
                if (this.copiedKey === key) this.copiedKey = null;
            }, 1600);
        },
        displayUrl(credential) {
            return credential.provider_host || credential.website_host || credential.provider_url || credential.website_url || 'No URL';
        }
    }"
    @keydown.escape.window="closeCredential()"
>
    <!-- Alert Message -->
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition:leave="transition ease-in duration-300 transform" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-400 flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-3">
                <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-medium">{{ session('message') }}</span>
            </div>
            <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- Header Section -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60">
        <div>
            <h3 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Credentials & Accounts</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Secure storage of accounts for hosting, control panels, and email.</p>
        </div>
        @if(!$showForm)
            <button wire:click="openCreateForm" class="inline-flex items-center justify-center px-3.5 py-2 text-xs font-semibold text-sky-700 bg-sky-100 hover:bg-sky-200 dark:bg-sky-950/40 dark:text-sky-400 border border-sky-200/40 dark:border-sky-900/40 rounded-xl shadow-sm hover:-translate-y-0.5 transition-all duration-200">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Add Credential
            </button>
        @endif
    </div>

    <!-- Inline Form Card (Add/Edit) -->
    @if($showForm)
        <div class="bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-5 shadow-inner space-y-5">
            <h4 class="font-semibold text-sm text-slate-800 dark:text-white flex items-center">
                <span class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></span>
                {{ $editingId ? 'Edit Credential' : 'Add New Credential' }}
            </h4>

            <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Type -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Access Type</label>
                    <select wire:model="type" class="w-full px-3.5 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                        <option value="web_2_0">Web Access 2.0</option>
                        <option value="hosting">Hosting / cPanel</option>
                        <option value="mail_admin">Mail Admin</option>
                        <option value="wordpress_admin">WordPress Admin</option>
                        <option value="other">Other</option>
                    </select>
                    @error('type') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Website Linkage -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Link to Website</label>
                    <select wire:model="website_id" class="w-full px-3.5 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                        <option value="">-- Select Website (Optional) --</option>
                        @foreach($project->websites()->orderBy('name')->get() as $web)
                            <option value="{{ $web->id }}">{{ $web->name }} ({{ parse_url($web->url, PHP_URL_HOST) ?: $web->url }})</option>
                        @endforeach
                    </select>
                    @error('website_id') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Provider URL -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Login URL (Provider URL)</label>
                    <input type="text" wire:model="provider_url" placeholder="https://example.com/login" class="w-full px-3.5 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                    @error('provider_url') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Login -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Login / Email <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="login" placeholder="admin@domain.com" class="w-full px-3.5 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                    @error('login') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Password -->
                <div x-data="{ showPw: false }" class="relative">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Password <span class="text-rose-500">*</span></label>
                    <input :type="showPw ? 'text' : 'password'" wire:model="password" placeholder="••••••••••••" class="w-full pl-3.5 pr-10 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                    
                    <button type="button" @click="showPw = !showPw" class="absolute right-3.5 bottom-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg x-show="!showPw" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="showPw" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                    @error('password') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Comments -->
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Comments / Extra Info</label>
                    <textarea wire:model="comments" rows="2" placeholder="Notes about this account..." class="w-full px-3.5 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150"></textarea>
                    @error('comments') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Form Buttons -->
                <div class="md:col-span-2 flex items-center justify-end space-x-2 pt-2">
                    <button type="button" wire:click="cancel" class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl transition-all duration-150">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100/80 dark:bg-indigo-950/30 dark:text-indigo-400 border border-indigo-200/40 dark:border-indigo-800/40 rounded-xl transition-all duration-150">
                        Save
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Credential Vault Cards -->
    <div class="space-y-4">
        @if($credentials->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($credentials as $cred)
                    @php
                        $typeLabel = match($cred->type) {
                            'hosting' => 'Hosting',
                            'wordpress_admin' => 'WP Admin',
                            'mail_admin' => 'Mail Admin',
                            'web_2_0' => 'Web 2.0',
                            default => 'Other',
                        };
                        $credentialName = $cred->website
                            ? $cred->website->name . ' / ' . $typeLabel
                            : $typeLabel . ' Credential';
                        $providerHost = $cred->provider_url ? (parse_url($cred->provider_url, PHP_URL_HOST) ?: $cred->provider_url) : null;
                        $websiteHost = $cred->website?->url ? (parse_url($cred->website->url, PHP_URL_HOST) ?: $cred->website->url) : null;
                        $credentialPayload = [
                            'id' => $cred->id,
                            'name' => $credentialName,
                            'type' => $cred->type,
                            'type_label' => $typeLabel,
                            'website_name' => $cred->website?->name,
                            'website_url' => $cred->website?->url,
                            'website_host' => $websiteHost,
                            'provider_url' => $cred->provider_url,
                            'provider_host' => $providerHost,
                            'login' => $cred->login,
                            'password' => $cred->password,
                            'comments' => $cred->comments,
                            'fields' => [
                                ['key' => 'provider_url', 'label' => 'Login URL', 'value' => $cred->provider_url, 'isSecret' => false],
                                ['key' => 'login', 'label' => 'Login / Email', 'value' => $cred->login, 'isSecret' => false],
                                ['key' => 'password', 'label' => 'Password', 'value' => $cred->password, 'isSecret' => true],
                            ],
                        ];
                    @endphp

                    <article
                        class="group relative overflow-hidden rounded-2xl border border-slate-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 p-4 shadow-sm hover:-translate-y-0.5 hover:border-sky-200 dark:hover:border-sky-900/60 hover:shadow-lg hover:shadow-sky-500/5 transition-all duration-200"
                    >
                        <button
                            type="button"
                            @click="openCredential(@js($credentialPayload))"
                            class="w-full text-left focus:outline-none"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="h-10 w-10 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                                        @if($cred->type === 'hosting')
                                            <i class="fa-solid fa-server text-sm"></i>
                                        @elseif($cred->type === 'wordpress_admin')
                                            <i class="fa-brands fa-wordpress text-base"></i>
                                        @elseif($cred->type === 'mail_admin')
                                            <i class="fa-solid fa-envelope text-sm"></i>
                                        @elseif($cred->type === 'web_2_0')
                                            <i class="fa-solid fa-window-restore text-sm"></i>
                                        @else
                                            <i class="fa-solid fa-key text-sm"></i>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-sm font-bold text-slate-800 dark:text-white truncate">{{ $credentialName }}</h4>
                                        <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate font-mono mt-0.5">
                                            {{ $providerHost ?: ($websiteHost ?: 'No URL linked') }}
                                        </p>
                                    </div>
                                </div>

                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg border text-[10px] font-bold uppercase tracking-wider shrink-0
                                    @if($cred->type === 'hosting') bg-purple-50 text-purple-700 border-purple-100 dark:bg-purple-950/30 dark:text-purple-400 dark:border-purple-900/40
                                    @elseif($cred->type === 'wordpress_admin') bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900/40
                                    @elseif($cred->type === 'mail_admin') bg-sky-50 text-sky-700 border-sky-100 dark:bg-sky-950/30 dark:text-sky-400 dark:border-sky-900/40
                                    @elseif($cred->type === 'web_2_0') bg-indigo-50 text-indigo-700 border-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-400 dark:border-indigo-900/40
                                    @else bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700 @endif">
                                    {{ $typeLabel }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <div class="rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/70 px-3 py-2 min-w-0">
                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Login</span>
                                    <span class="block text-xs font-semibold text-slate-700 dark:text-slate-300 truncate mt-0.5">{{ $cred->login }}</span>
                                </div>
                                <div class="rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/70 px-3 py-2">
                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Secret</span>
                                    <span class="block text-xs font-mono font-bold text-slate-400 tracking-widest mt-0.5">••••••••</span>
                                </div>
                            </div>

                            @if($cred->comments)
                                <p class="mt-3 text-[11px] leading-relaxed text-slate-500 dark:text-slate-400 line-clamp-2">{{ $cred->comments }}</p>
                            @endif
                        </button>

                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between">
                            <button
                                type="button"
                                @click="openCredential(@js($credentialPayload))"
                                class="inline-flex items-center text-[11px] font-bold text-sky-700 dark:text-sky-400 hover:text-sky-800 dark:hover:text-sky-300 transition-colors"
                            >
                                Open details
                                <svg class="h-3.5 w-3.5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                            <div class="flex items-center gap-1">
                                <button type="button" wire:click="edit({{ $cred->id }})" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-amber-600 dark:hover:text-amber-400 transition-all duration-150" title="Edit">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <button type="button" wire:click="delete({{ $cred->id }})" wire:confirm="Are you sure you want to delete this credential?" class="p-1.5 rounded-lg text-slate-400 hover:bg-rose-50 dark:hover:bg-rose-950/20 hover:text-rose-600 dark:hover:text-rose-400 transition-all duration-150" title="Delete">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-6 py-12 text-center">
                <div class="mx-auto h-11 w-11 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-400">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h4 class="mt-4 text-sm font-bold text-slate-700 dark:text-slate-200">No credentials yet</h4>
                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Add the first account for this company or website.</p>
            </div>
        @endif
    </div>

    <!-- Credential Details Modal -->
    <div
        x-show="selectedCredential"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="credential-modal-title"
        role="dialog"
        aria-modal="true"
        style="display: none;"
    >
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div
                x-show="selectedCredential"
                x-transition.opacity
                class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"
                aria-hidden="true"
                @click="closeCredential()"
            ></div>

            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

            <div
                x-show="selectedCredential"
                x-transition:enter="transition ease-out duration-180"
                x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-120"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                class="relative inline-block w-full max-w-2xl transform overflow-hidden rounded-2xl border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 text-left align-bottom shadow-2xl sm:my-8 sm:align-middle"
            >
                <template x-if="selectedCredential">
                    <div>
                        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/60 dark:bg-slate-950/30 flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-lg border text-[10px] font-bold uppercase tracking-wider"
                                        :class="credentialTypeStyles[selectedCredential.type] || credentialTypeStyles.other"
                                        x-text="selectedCredential.type_label"
                                    ></span>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Credential Vault</span>
                                </div>
                                <h3 id="credential-modal-title" class="mt-2 font-outfit text-xl font-extrabold text-slate-800 dark:text-white truncate" x-text="selectedCredential.name"></h3>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 font-mono truncate" x-text="displayUrl(selectedCredential)"></p>
                            </div>
                            <button type="button" @click="closeCredential()" class="p-2 rounded-xl text-slate-400 hover:bg-white dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-4">
                            <template x-for="field in selectedCredential.fields.filter(field => field.value)" :key="field.key">
                                <div class="rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/30 p-4">
                                    <div class="flex items-center justify-between gap-3 mb-2">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500" x-text="field.label"></span>
                                        <div class="flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                x-show="field.isSecret"
                                                @click="visibleSecrets[field.key] = !visibleSecrets[field.key]"
                                                class="p-1.5 rounded-lg text-slate-400 hover:bg-white dark:hover:bg-slate-900 hover:text-slate-700 dark:hover:text-slate-200 transition-colors"
                                                title="Show or hide secret"
                                            >
                                                <svg x-show="!visibleSecrets[field.key]" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <svg x-show="visibleSecrets[field.key]" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                                </svg>
                                            </button>
                                            <button
                                                type="button"
                                                @click="copyField(field.key, field.value)"
                                                class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-[10px] font-bold text-slate-500 hover:text-slate-800 dark:hover:text-slate-100 transition-colors"
                                            >
                                                <svg x-show="copiedKey !== field.key" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414A1 1 0 0120 8.414V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                                </svg>
                                                <svg x-show="copiedKey === field.key" class="h-3.5 w-3.5 mr-1 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span x-text="copiedKey === field.key ? 'Copied' : 'Copy'"></span>
                                            </button>
                                        </div>
                                    </div>
                                    <div
                                        class="min-h-[2.5rem] rounded-xl border border-slate-200/70 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 font-mono text-sm text-slate-800 dark:text-slate-100 break-all transition-all"
                                        :class="field.isSecret && !visibleSecrets[field.key] ? 'blur-sm select-none text-slate-400 tracking-widest' : ''"
                                        x-text="field.isSecret && !visibleSecrets[field.key] ? '••••••••••••••••' : field.value"
                                    ></div>
                                </div>
                            </template>

                            <template x-if="selectedCredential.comments">
                                <div class="rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/30 p-4">
                                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Comments</span>
                                    <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300 whitespace-pre-wrap" x-text="selectedCredential.comments"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
