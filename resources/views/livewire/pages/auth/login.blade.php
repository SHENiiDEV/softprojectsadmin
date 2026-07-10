<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="backdrop-blur-lg bg-slate-950/45 border border-slate-800/80 shadow-2xl rounded-3xl p-8 space-y-6 ring-1 ring-white/5">
    <div class="text-center">
        <h2 class="font-outfit font-extrabold text-2xl text-white tracking-tight">Welcome Back</h2>
        <p class="text-xs text-slate-400 mt-1.5">Sign in to your SoftProject Hub account</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-4">
        <!-- Email Address -->
        <div>
            <label for="email" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Email Address</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                    <i class="fa-solid fa-envelope text-xs"></i>
                </div>
                <input wire:model="form.email" id="email" 
                       type="email" name="email" required autofocus autocomplete="username"
                       placeholder="you@domain.com"
                       class="w-full pl-9 pr-3.5 py-2.5 bg-slate-900/50 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150 shadow-inner" />
            </div>
            <x-input-error :messages="$errors->get('form.email')" class="mt-1" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Password</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                    <i class="fa-solid fa-lock text-xs"></i>
                </div>
                <input wire:model="form.password" id="password" 
                       type="password" name="password" required autocomplete="current-password"
                       placeholder="••••••••••••"
                       class="w-full pl-9 pr-3.5 py-2.5 bg-slate-900/50 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150 shadow-inner" />
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-1" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between text-xs pt-1">
            <label for="remember" class="inline-flex items-center cursor-pointer select-none">
                <input wire:model="form.remember" id="remember" type="checkbox" 
                       class="rounded bg-slate-900/50 border-slate-800 text-sky-600 focus:ring-sky-500/20 focus:ring-offset-slate-900 mr-2" name="remember">
                <span class="text-slate-400 hover:text-slate-350 transition-colors">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sky-400 hover:text-sky-300 font-medium transition-colors" href="{{ route('password.request') }}" wire:navigate>
                    Forgot password?
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <div class="pt-3">
            <button type="submit" 
                    class="w-full py-2.5 bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-600 hover:to-indigo-700 text-white font-bold rounded-xl text-xs transition-all duration-150 shadow-lg shadow-sky-500/10 hover:shadow-sky-500/20 hover:scale-[1.01] transform active:scale-[0.99] flex items-center justify-center gap-2">
                <span>Sign In</span>
                <i class="fa-solid fa-arrow-right-to-bracket text-xs"></i>
            </button>
        </div>
    </form>
</div>
