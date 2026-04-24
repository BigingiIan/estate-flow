<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use function Livewire\Volt\layout;
use function Livewire\Volt\rules;
use function Livewire\Volt\state;

layout('layouts.guest');

state([
    'name' => '',
    'email' => '',
    'phone' => '',
    'password' => '',
    'password_confirmation' => '',
]);

rules([
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
    'phone' => ['required', 'string', 'max:20'],
    'password' => [
        'required',
        'string',
        'confirmed',
        Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised(), // optional – checks against leaked passwords
    ],
]);

$register = function () {
    $validated = $this->validate();
    $validated['password'] = Hash::make($validated['password']);
    $validated['role'] = 'landlord';

    event(new Registered($user = User::create($validated)));

    Auth::login($user);

    $this->redirect(route('dashboard', absolute: false), navigate: true);
};
?>

<div>
    <h2 class="text-3xl font-bold text-slate-700">Create Account</h2>
    <p class="mt-1 text-slate-400 text-sm">Enter your details to join the ecosystem.</p>

    <form wire:submit="register" class="mt-8 space-y-6">

        {{-- Full Name --}}
        <div>
            <label for="name" class="block text-xs font-semibold tracking-widest text-slate-500 uppercase mb-2">
                Full Name
            </label>
            <input wire:model="name"
                id="name"
                type="text"
                name="name"
                required
                autofocus
                autocomplete="name"
                placeholder="John Doe"
                class="w-full border-0 border-b border-slate-300 bg-transparent px-0 py-2 text-slate-700 placeholder-slate-300 focus:border-slate-600 focus:outline-none focus:ring-0 transition-colors" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="block text-xs font-semibold tracking-widest text-slate-500 uppercase mb-2">
                Email Address
            </label>
            <input wire:model="email"
                id="email"
                type="email"
                name="email"
                required
                autocomplete="username"
                placeholder="name@company.com"
                class="w-full border-0 border-b border-slate-300 bg-transparent px-0 py-2 text-slate-700 placeholder-slate-300 focus:border-slate-600 focus:outline-none focus:ring-0 transition-colors" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        {{-- Phone --}}
        <div>
            <label for="phone" class="block text-xs font-semibold tracking-widest text-slate-500 uppercase mb-2">
                Phone Number
            </label>
            <input wire:model="phone"
                id="phone"
                type="tel"
                name="phone"
                required
                autocomplete="tel"
                placeholder="+254700000000"
                class="w-full border-0 border-b border-slate-300 bg-transparent px-0 py-2 text-slate-700 placeholder-slate-300 focus:border-slate-600 focus:outline-none focus:ring-0 transition-colors" />
            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-xs font-semibold tracking-widest text-slate-500 uppercase mb-2">
                Password
            </label>
            <div class="relative">
                <input wire:model="password"
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
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
            {{-- Password requirements hint --}}
            <ul class="text-xs text-slate-400 mt-2 space-y-1 list-disc list-inside">
                <li>Minimum 8 characters</li>
                <li>At least one uppercase letter (A–Z)</li>
                <li>At least one lowercase letter (a–z)</li>
                <li>At least one number (0–9)</li>
                <li>At least one special character (!@#$%^&* etc.)</li>
            </ul>
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        {{-- Confirm Password --}}
        <div>
            <label for="password_confirmation" class="block text-xs font-semibold tracking-widest text-slate-500 uppercase mb-2">
                Confirm Password
            </label>
            <div class="relative">
                <input wire:model="password_confirmation"
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
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
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        {{-- Submit --}}
        <button type="submit"
            class="w-full bg-slate-600 hover:bg-slate-700 text-white text-sm font-semibold tracking-widest uppercase py-3 px-6 transition-colors">
            Sign Up
        </button>

        {{-- Back to login --}}
        <p class="text-center text-sm text-slate-400">
            <a href="{{ route('login') }}" wire:navigate
                class="inline-flex items-center gap-1 hover:text-slate-600 transition-colors">
                ← Back to Login
            </a>
        </p>

    </form>
    <div class="mt-6">
    <div class="relative">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-slate-300"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white text-slate-400">Or continue with</span>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('login.google') }}"
            class="w-full flex items-center justify-center gap-3 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold py-3 px-6 border border-slate-300 transition-colors">
            <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Sign in with Google
        </a>
    </div>
    </div>
</div>