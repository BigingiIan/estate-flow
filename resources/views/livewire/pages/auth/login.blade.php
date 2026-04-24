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
            <div class="relative">
                <input wire:model="form.password"
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="w-full border-0 border-b border-slate-300 bg-transparent px-0 py-2 text-slate-700 placeholder-slate-300 focus:border-slate-600 focus:outline-none focus:ring-0 transition-colors pr-8" />
                <button type="button"
                    x-data="{ show: false }"
                    @click="show = !show; $el.previousElementSibling.type = show ? 'text' : 'password'"
                    class="absolute inset-y-0 right-0 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                    <svg x-show="!show" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                        <svg x-show="show" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
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