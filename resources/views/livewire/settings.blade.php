<div class="space-y-8">
    <x-slot name="header">
        System Settings
    </x-slot>

    <!-- Header bar -->
    <div class="pb-4 border-b border-slate-100 dark:border-slate-800/60">
        <h1 class="font-outfit font-extrabold text-3xl text-slate-700 dark:text-white tracking-tight">System Settings</h1>
        <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Configure global application parameters, SMTP mail server settings, and Telegram Bot options.</p>
    </div>

    @if (session()->has('message'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-900/40 text-sm font-semibold">
            {{ session('message') }}
        </div>
    @endif

    <!-- Tabs Navigation -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-0">
        <button type="button" wire:click="$set('activeTab', 'general')"
                class="px-4 py-2.5 text-sm font-bold border-b-2 transition-all duration-200 cursor-pointer -mb-px
                       {{ $activeTab === 'general' ? 'border-sky-500 text-sky-600 dark:text-sky-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
            General Settings
        </button>
        <button type="button" wire:click="$set('activeTab', 'roles')"
                class="px-4 py-2.5 text-sm font-bold border-b-2 transition-all duration-200 cursor-pointer -mb-px
                       {{ $activeTab === 'roles' ? 'border-sky-500 text-sky-600 dark:text-sky-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
            Roles & Permissions
        </button>
    </div>

    @if ($activeTab === 'general')
    <form wire:submit.prevent="save" class="space-y-8">
        <!-- Grid for Settings Modules -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Side: Branding & Defaults -->
            <div class="space-y-8">
                <!-- Branding Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-6">
                    <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800/45 flex items-center">
                        <i class="fa-solid fa-paintbrush text-sky-500 mr-2.5"></i> Custom Branding
                    </h2>
                    
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="app_name" :value="__('App Name')" />
                            <x-text-input wire:model="app_name" id="app_name" type="text" class="mt-1 block w-full rounded-xl" required />
                            <x-input-error class="mt-2" :messages="$errors->get('app_name')" />
                        </div>

                        <!-- App Logo Upload -->
                        <div class="flex items-center space-x-6 bg-slate-50/50 dark:bg-slate-950/40 p-4 rounded-xl border border-slate-100 dark:border-slate-800/60">
                            <div class="shrink-0">
                                @if ($app_logo)
                                    <img class="h-14 w-14 object-contain rounded bg-white p-1" src="{{ $app_logo->temporaryUrl() }}" alt="Logo Preview">
                                @elseif ($app_logo_path)
                                    <img class="h-14 w-14 object-contain rounded bg-white p-1" src="{{ asset('storage/' . $app_logo_path) }}" alt="App Logo">
                                @else
                                    <div class="h-14 w-14 rounded-xl bg-gradient-to-tr from-sky-400 to-indigo-600 text-white font-black text-xl flex items-center justify-center">
                                        PH
                                    </div>
                                @endif
                            </div>
                            <label class="block">
                                <span class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Company Logo</span>
                                <input type="file" wire:model="app_logo" class="block w-full text-xs text-slate-500
                                  file:mr-4 file:py-1.5 file:px-3
                                  file:rounded-xl file:border-0
                                  file:text-xs file:font-semibold
                                  file:bg-sky-50 file:text-sky-700
                                  hover:file:bg-sky-100
                                  dark:file:bg-slate-800 dark:file:text-sky-400
                                  cursor-pointer
                                " />
                                <x-input-error class="mt-2" :messages="$errors->get('app_logo')" />
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Defaults Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-6">
                    <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800/45 flex items-center">
                        <i class="fa-solid fa-gears text-sky-500 mr-2.5"></i> Operational Defaults
                    </h2>
                    
                    <div>
                        <x-input-label for="default_manager_id" :value="__('Default Company Manager')" />
                        <select wire:model="default_manager_id" id="default_manager_id" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-sky-500 focus:ring-sky-500 shadow-sm text-sm">
                            <option value="">-- No Default Manager --</option>
                            @foreach ($managers as $manager)
                                <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('default_manager_id')" />
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">This user will be assigned automatically when new projects/companies are created without a manager.</p>
                    </div>
                </div>
            </div>

            <!-- Right Side: SMTP Configurations -->
            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-6">
                <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800/45 flex items-center">
                    <i class="fa-solid fa-envelope text-sky-500 mr-2.5"></i> Mail Server Config (SMTP)
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="mail_host" :value="__('SMTP Host')" />
                        <x-text-input wire:model="mail_host" id="mail_host" type="text" class="mt-1 block w-full rounded-xl" placeholder="smtp.mailgun.org" />
                        <x-input-error class="mt-2" :messages="$errors->get('mail_host')" />
                    </div>

                    <div>
                        <x-input-label for="mail_port" :value="__('SMTP Port')" />
                        <x-text-input wire:model="mail_port" id="mail_port" type="number" class="mt-1 block w-full rounded-xl" placeholder="587" />
                        <x-input-error class="mt-2" :messages="$errors->get('mail_port')" />
                    </div>

                    <div>
                        <x-input-label for="mail_encryption" :value="__('Encryption')" />
                        <select wire:model="mail_encryption" id="mail_encryption" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-sky-500 focus:ring-sky-500 shadow-sm text-sm">
                            <option value="null">None</option>
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('mail_encryption')" />
                    </div>

                    <div>
                        <x-input-label for="mail_username" :value="__('SMTP Username')" />
                        <x-text-input wire:model="mail_username" id="mail_username" type="text" class="mt-1 block w-full rounded-xl" />
                        <x-input-error class="mt-2" :messages="$errors->get('mail_username')" />
                    </div>

                    <div>
                        <x-input-label for="mail_password" :value="__('SMTP Password')" />
                        <x-text-input wire:model="mail_password" id="mail_password" type="password" class="mt-1 block w-full rounded-xl" />
                        <x-input-error class="mt-2" :messages="$errors->get('mail_password')" />
                    </div>

                    <div>
                        <x-input-label for="mail_from_address" :value="__('Sender Email Address')" />
                        <x-text-input wire:model="mail_from_address" id="mail_from_address" type="email" class="mt-1 block w-full rounded-xl" placeholder="noreply@domain.com" />
                        <x-input-error class="mt-2" :messages="$errors->get('mail_from_address')" />
                    </div>

                    <div>
                        <x-input-label for="mail_from_name" :value="__('Sender Name')" />
                        <x-text-input wire:model="mail_from_name" id="mail_from_name" type="text" class="mt-1 block w-full rounded-xl" placeholder="SoftProject Manager" />
                        <x-input-error class="mt-2" :messages="$errors->get('mail_from_name')" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Telegram Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-6">
            <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800/45 flex items-center">
                <i class="fa-brands fa-telegram text-sky-500 mr-2.5"></i> Telegram Integration
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="telegram_bot_token" :value="__('Telegram Bot Token')" />
                    <x-text-input wire:model="telegram_bot_token" id="telegram_bot_token" type="password" class="mt-1 block w-full rounded-xl" placeholder="bot123456:ABC-DEF" />
                    <x-input-error class="mt-2" :messages="$errors->get('telegram_bot_token')" />
                </div>

                <div>
                    <x-input-label for="telegram_bot_username" :value="__('Telegram Bot Username')" />
                    <x-text-input wire:model="telegram_bot_username" id="telegram_bot_username" type="text" class="mt-1 block w-full rounded-xl" placeholder="MyCoolCrmBot" />
                    <x-input-error class="mt-2" :messages="$errors->get('telegram_bot_username')" />
                </div>
            </div>
        </div>

        <!-- Submit bar -->
        <div class="flex items-center justify-end pt-4">
            <x-primary-button class="rounded-xl px-6 py-3">
                <i class="fa-solid fa-floppy-disk mr-2"></i> Save Settings
            </x-primary-button>
        </div>
    </form>
    @endif

    @if ($activeTab === 'roles')
        <!-- Roles & Permissions Matrix Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-6">
            <div>
                <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white flex items-center">
                    <i class="fa-solid fa-user-shield text-sky-500 mr-2.5"></i> Roles & Permissions Matrix
                </h2>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Configure which sections and functions will be available to each default role. System Administrator role always retains full permissions.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800/60 text-slate-400 dark:text-slate-500 text-[10px] font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">Permission</th>
                            <th class="py-3 px-4 text-center">Manager</th>
                            <th class="py-3 px-4 text-center">Curator</th>
                            <th class="py-3 px-4 text-center">Worker</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @foreach ($permissionsList as $permName => $permLabel)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-950/20 transition-colors">
                                <td class="py-3.5 px-4">
                                    <span class="block text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $permLabel }}</span>
                                    <span class="block text-[9px] font-mono text-slate-400 dark:text-slate-500 mt-0.5">{{ $permName }}</span>
                                </td>
                                @foreach (['manager', 'curator', 'worker'] as $roleName)
                                    <td class="py-3.5 px-4 text-center">
                                        <label class="inline-flex items-center justify-center cursor-pointer p-1">
                                            <input type="checkbox" 
                                                   wire:click="togglePermission('{{ $roleName }}', '{{ $permName }}')"
                                                   {{ ($rolePermissions[$roleName][$permName] ?? false) ? 'checked' : '' }}
                                                   class="rounded dark:bg-slate-950 border-slate-300 dark:border-slate-800 text-sky-600 focus:ring-sky-500/20 w-4 h-4 transition-all">
                                        </label>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
