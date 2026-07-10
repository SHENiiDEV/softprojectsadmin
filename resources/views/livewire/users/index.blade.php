<div class="space-y-6">
    <x-slot name="header">
        User Management
    </x-slot>

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="font-outfit font-extrabold text-2xl text-slate-800 dark:text-white tracking-tight">System Users</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Add, edit roles, reset passwords, and monitor Telegram connection status.</p>
        </div>
        <div>
            <button type="button" 
                    wire:click="openCreateModal" 
                    class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-sky-700 bg-sky-100 hover:bg-sky-200 dark:bg-sky-950/40 dark:text-sky-400 border border-sky-200/40 dark:border-sky-900/40 rounded-xl shadow-sm hover:-translate-y-0.5 transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Create User
            </button>
        </div>
    </div>

    <!-- Alert / Messages -->
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

    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition:leave="transition ease-in duration-300 transform" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-800/40 text-rose-800 dark:text-rose-400 flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-3">
                <svg class="h-5 w-5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
            <button @click="show = false" class="text-rose-500 hover:text-rose-700 dark:hover:text-rose-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- Search & Filters -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <!-- Search -->
            <div class="relative md:col-span-8">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Search by name or email address..." 
                       class="w-full pl-11 pr-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200">
            </div>

            <!-- Role filter -->
            <div class="md:col-span-4">
                <select wire:model.live="filterRole" 
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200">
                    <option value="">All user roles</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="curator">Curator</option>
                    <option value="worker">Worker</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-slate-400 dark:text-slate-500 text-xs font-semibold uppercase border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-950/20">
                        <th class="py-4 px-6">User</th>
                        <th class="py-4 px-6">Email</th>
                        <th class="py-4 px-6">Role</th>
                        <th class="py-4 px-6">Telegram Status</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-sm">
                    @forelse($users as $userItem)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors duration-150">
                            <!-- User info name -->
                            <td class="py-4 px-6 font-semibold text-slate-800 dark:text-slate-200">
                                <div class="flex items-center space-x-3">
                                    <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-sky-400 to-indigo-500 flex items-center justify-center font-bold text-white uppercase text-xs">
                                        {{ substr($userItem->name, 0, 2) }}
                                    </div>
                                    <span>{{ $userItem->name }}</span>
                                </div>
                            </td>

                            <!-- Email -->
                            <td class="py-4 px-6 text-slate-500 dark:text-slate-400">
                                {{ $userItem->email }}
                            </td>

                            <!-- Role -->
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider
                                    @if($userItem->hasRole('admin')) bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400
                                    @elseif($userItem->hasRole('manager')) bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400
                                    @elseif($userItem->hasRole('curator')) bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400
                                    @else bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 @endif">
                                    {{ $userItem->roles->first()?->name ?? 'None' }}
                                </span>
                            </td>

                            <!-- Telegram connection badge -->
                            <td class="py-4 px-6">
                                @if($userItem->telegram_id)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ $userItem->telegram_username ? '@' . $userItem->telegram_username : 'Linked' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        Not linked
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-6 text-right space-x-1.5 whitespace-nowrap">
                                <!-- Edit -->
                                 <button type="button"
                                         wire:click="openEditModal({{ $userItem->id }})"
                                         class="inline-flex items-center justify-center p-2 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-sky-600 dark:hover:text-sky-400 transition-colors duration-150"
                                         title="Edit">
                                     <i class="fa-solid fa-pen"></i>
                                 </button>

                                <!-- Password reset -->
                                <button type="button" 
                                        wire:click="openResetModal({{ $userItem->id }})" 
                                        class="inline-flex items-center justify-center p-2 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-amber-600 dark:hover:text-amber-400 transition-colors duration-150" 
                                        title="Reset password">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m-2 4a2 2 0 012 2m-2-4a2 2 0 012-2m-2 4h.01M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </button>

                                <!-- Delete -->
                                @if(auth()->user()->hasRole('admin'))
                                    <button type="button"
                                             wire:click="deleteUser({{ $userItem->id }})"
                                             wire:confirm="Are you sure you want to delete this user?"
                                             class="inline-flex items-center justify-center p-2 rounded-lg text-slate-400 hover:bg-rose-50 dark:hover:bg-rose-950/20 hover:text-rose-600 dark:hover:text-rose-400 transition-colors duration-150"
                                             title="Delete"
                                             @if(auth()->id() === $userItem->id) disabled class="opacity-30 cursor-not-allowed" @endif>
                                         <i class="fa-solid fa-trash"></i>
                                     </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <svg class="h-10 w-10 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    <span class="text-sm">No users found matching the given criteria.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-950/20">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Modals -->
    <!-- Create/Edit Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
                <div class="flex justify-between items-center pb-2 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">
                        {{ $editingUserId ? 'Edit User' : 'Create User' }}
                    </h3>
                    <button type="button" wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveUser" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Name</label>
                        <input type="text" 
                               wire:model="name" 
                               class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm focus:ring-sky-500/20 focus:border-sky-500" 
                               required>
                        @error('name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Email</label>
                        <input type="email" 
                               wire:model="email" 
                               class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm focus:ring-sky-500/20 focus:border-sky-500" 
                               required>
                        @error('email') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    @if(!$editingUserId)
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Password</label>
                            <input type="password" 
                                   wire:model="password" 
                                   class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm focus:ring-sky-500/20 focus:border-sky-500" 
                                   required>
                            @error('password') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Role</label>
                        <select wire:model="role" 
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm focus:ring-sky-500/20 focus:border-sky-500" 
                                required>
                            <option value="worker">Worker</option>
                            <option value="curator">Curator</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                        @error('role') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" 
                                wire:click="$set('showModal', false)" 
                                class="px-4 py-2 text-sm font-semibold text-slate-500 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 rounded-xl transition duration-150">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-semibold text-white bg-indigo-650 hover:bg-indigo-700 dark:bg-indigo-600 dark:hover:bg-indigo-500 rounded-xl transition duration-150">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Password Reset Modal -->
    @if($showResetModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
                <div class="flex justify-between items-center pb-2 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Reset User Password</h3>
                    <button type="button" wire:click="$set('showResetModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="resetPassword" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">New Password</label>
                        <input type="password" 
                               wire:model="newPassword" 
                               class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm focus:ring-sky-500/20 focus:border-sky-500" 
                               required>
                        @error('newPassword') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" 
                                wire:click="$set('showResetModal', false)" 
                                class="px-4 py-2 text-sm font-semibold text-slate-500 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 rounded-xl transition duration-150">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-semibold text-white bg-amber-600 hover:bg-amber-700 dark:bg-amber-600 dark:hover:bg-amber-500 rounded-xl transition duration-150">
                            Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
