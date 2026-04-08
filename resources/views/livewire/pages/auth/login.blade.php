<?php
use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use function Livewire\Volt\form;
use function Livewire\Volt\layout;

layout('layouts.guest');
form(LoginForm::class);

$login = function () {
    $this->validate();
    $this->form->authenticate();
    Session::regenerate();
    $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
};
?>

<div>
    <h2 class="text-3xl font-bold text-slate-700">Login</h2>
    <p class="mt-1 text-slate-400 text-sm">Enter your credentials to access your dashboard.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form wire:submit="login" class="mt-8 space-y-6">

        {{-- Email --}}
        <div>
            <label for="email" class="block text-xs font-semibold tracking-widest text-slate-500 uppercase mb-2">
                Email Address
            </label>
            <input wire:model="form.email"
                id="email"
                type="email"
                name="email"
                required
                autofocus
                autocomplete="username"
                placeholder="name@company.com"
                class="w-full border-0 border-b border-slate-300 bg-transparent px-0 py-2 text-slate-700 placeholder-slate-300 focus:border-slate-600 focus:outline-none focus:ring-0 transition-colors" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-1" />
        </div>

        {{-- Password --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <label for="password" class="block text-xs font-semibold tracking-widest text-slate-500 uppercase">
                    Password
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" wire:navigate
                        class="text-xs font-semibold tracking-widest text-slate-400 uppercase hover:text-slate-600 transition-colors">
                        Forgot Password?
                    </a>
                @endif
            </div>
            <input wire:model="form.password"
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                class="w-full border-0 border-b border-slate-300 bg-transparent px-0 py-2 text-slate-700 placeholder-slate-300 focus:border-slate-600 focus:outline-none focus:ring-0 transition-colors" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-1" />
        </div>

        {{-- Remember me --}}
        <div class="flex items-center gap-2">
            <input wire:model="form.remember"
                id="remember"
                type="checkbox"
                class="rounded border-slate-300 text-slate-600 shadow-sm focus:ring-slate-500">
            <label for="remember" class="text-sm text-slate-500">Remember me</label>
        </div>

        {{-- Submit --}}
        <button type="submit"
            class="w-full bg-slate-600 hover:bg-slate-700 text-white text-sm font-semibold tracking-widest uppercase py-3 px-6 transition-colors">
            Login
        </button>

        {{-- Register link --}}
        @if (Route::has('register'))
            <p class="text-center text-sm text-slate-400">
                Don't have an account?
                <a href="{{ route('register') }}" wire:navigate
                    class="font-semibold text-slate-600 hover:text-slate-800 transition-colors">
                    Create an account
                </a>
            </p>
        @endif

    </form>
</div>