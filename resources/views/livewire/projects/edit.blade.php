<div class="space-y-6">
    <x-slot name="header">
        Edit Company: {{ $project->name }}
    </x-slot>

    <!-- Top Action Bar -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60">
        <div>
            <h1 class="font-outfit font-extrabold text-2xl text-slate-800 dark:text-white tracking-tight">Edit Company</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Modify the necessary company and assigned director details.</p>
        </div>
        <div>
            <a href="{{ route('projects.index') }}" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm transition-all duration-200" wire:navigate>
                Cancel
            </a>
        </div>
    </div>

    <!-- Form Container -->
    <form wire:submit="save" class="space-y-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Side: Company Form Card -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-6">
                    <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800/40">Company Details</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Name -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Company Name <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="name" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200" placeholder="e.g. Alpha Technology Ltd">
                            @error('name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Website -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Website</label>
                            <input type="text" wire:model="website" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200" placeholder="https://example.com">
                            @error('website') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- MCC Code -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">MCC Code (Industry)</label>
                            <input type="text" wire:model="mcc" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200" placeholder="e.g. 5732">
                            @error('mcc') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Project Status</label>
                            <select wire:model="status" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200">
                                <option value="onboarding">Onboarding</option>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                            </select>
                            @error('status') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Integration Status -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Integration Status</label>
                            <select wire:model="integration_status" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200">
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                            @error('integration_status') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Client Selection -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Client</label>
                            <select wire:model="client_id" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200">
                                <option value="">No Client</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                @endforeach
                            </select>
                            @error('client_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- UBO -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Ultimate Beneficial Owner (UBO)</label>
                            <input type="text" wire:model="ubo" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200" placeholder="UBO Name">
                            @error('ubo') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Manager ID -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Assigned Manager</label>
                            <select wire:model="manager_id" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200">
                                <option value="">Not assigned</option>
                                @foreach($managers as $manager)
                                    <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                @endforeach
                            </select>
                            @error('manager_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Phone: Krisp -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Phone (Krisp)</label>
                            <input type="text" wire:model="phone_krisp" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200" placeholder="+123456789">
                            @error('phone_krisp') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Phone: Zadarma -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Phone (Zadarma)</label>
                            <input type="text" wire:model="phone_zadarma" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200" placeholder="+123456789">
                            @error('phone_zadarma') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Email: Corporate -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Email (Corporate)</label>
                            <input type="email" wire:model="email_corporate" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200" placeholder="corp@company.com">
                            @error('email_corporate') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Email: Private -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Email (Private)</label>
                            <input type="email" wire:model="email_private" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200" placeholder="owner@gmail.com">
                            @error('email_private') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Notes -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Notes / Comments</label>
                            <textarea wire:model="notes" rows="4" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200" placeholder="Any additional information about the project..."></textarea>
                            @error('notes') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Director Form Card -->
            <div class="space-y-6">
                <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-6">
                    <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800/40">Director</h2>

                    <div class="space-y-5">
                        <!-- Director Name -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Director Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="director_name" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200" placeholder="Director name">
                            @error('director_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Fee Paid Status -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Fee Paid Status</label>
                            <select wire:model="director_fee_status" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200">
                                <option value="unpaid">Unpaid</option>
                                <option value="paid">Paid</option>
                                <option value="pending">Pending</option>
                            </select>
                            @error('director_fee_status') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Director Managed By -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Assigned Curator / Manager</label>
                            <select wire:model="director_managed_by" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200">
                                <option value="">Not assigned</option>
                                @foreach($allUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->roles->first()?->name }})</option>
                                @endforeach
                            </select>
                            @error('director_managed_by') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" class="w-full flex items-center justify-center px-4 py-3 text-sm font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-400 border border-indigo-200/40 dark:border-indigo-800/40 rounded-xl shadow-sm hover:-translate-y-0.5 transition-all duration-200">
                        <svg class="w-4.5 h-4.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
