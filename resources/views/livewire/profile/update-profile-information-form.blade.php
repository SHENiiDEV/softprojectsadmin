<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public $avatar;
    public string $avatar_path = '';
    public string $timezone = 'UTC';
    public string $language = 'en';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->avatar_path = $user->avatar_path ?? '';
        $this->timezone = $user->timezone ?? 'UTC';
        $this->language = $user->language ?? 'en';
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ];

        if (config('features.profile_settings', true)) {
            $rules['avatar'] = ['nullable', 'image', 'max:1024'];
            $rules['timezone'] = ['required', 'string'];
            $rules['language'] = ['required', 'string', 'in:en,ru'];
        }

        $validated = $this->validate($rules);

        if (config('features.profile_settings', true) && $this->avatar) {
            // Delete old avatar if exists
            if ($user->avatar_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar_path);
            }
            $validated['avatar_path'] = $this->avatar->store('avatars', 'public');
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
        $this->avatar_path = $user->avatar_path ?? '';

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information, avatar, locale, and timezone settings.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        @if(config('features.profile_settings', true))
        <!-- Avatar Section -->
        <div class="flex items-center space-x-6 bg-slate-50/50 dark:bg-slate-900/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/80">
            <div class="shrink-0 relative">
                @if ($this->avatar)
                    <img class="h-16 w-16 object-cover rounded-xl shadow-md" src="{{ $this->avatar->temporaryUrl() }}" alt="Avatar Preview">
                @else
                    <img class="h-16 w-16 object-cover rounded-xl shadow-md" src="{{ auth()->user()->avatar_url }}" alt="Current Avatar">
                @endif
            </div>
            <label class="block">
                <span class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Profile Avatar</span>
                <input type="file" wire:model="avatar" class="block w-full text-xs text-slate-500
                  file:mr-4 file:py-1.5 file:px-3
                  file:rounded-xl file:border-0
                  file:text-xs file:font-semibold
                  file:bg-sky-50 file:text-sky-700
                  hover:file:bg-sky-100
                  dark:file:bg-slate-800 dark:file:text-sky-400
                  cursor-pointer
                " />
                <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
            </label>
        </div>
        @endif

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full rounded-xl" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full rounded-xl" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button wire:click.prevent="sendVerification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        @if(config('features.profile_settings', true))
        <!-- Language Select -->
        <div>
            <x-input-label for="language" :value="__('Interface Language')" />
            <select wire:model="language" id="language" name="language" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-sky-500 focus:ring-sky-500 shadow-sm text-sm">
                <option value="en">English</option>
                <option value="ru">Русский</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('language')" />
        </div>

        <!-- Timezone Select -->
        <div>
            <x-input-label for="timezone" :value="__('Timezone')" />
            <select wire:model="timezone" id="timezone" name="timezone" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-sky-500 focus:ring-sky-500 shadow-sm text-sm">
                @foreach(timezone_identifiers_list() as $tz)
                    <option value="{{ $tz }}">{{ $tz }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('timezone')" />
        </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button class="rounded-xl">{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
