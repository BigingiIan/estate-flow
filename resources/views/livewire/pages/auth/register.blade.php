<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
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
    'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
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
            <input wire:model="password"
                id="password"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="••••••••"
                class="w-full border-0 border-b border-slate-300 bg-transparent px-0 py-2 text-slate-700 placeholder-slate-300 focus:border-slate-600 focus:outline-none focus:ring-0 transition-colors" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        {{-- Confirm Password --}}
        <div>
            <label for="password_confirmation" class="block text-xs font-semibold tracking-widest text-slate-500 uppercase mb-2">
                Confirm Password
            </label>
            <input wire:model="password_confirmation"
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="••••••••"
                class="w-full border-0 border-b border-slate-300 bg-transparent px-0 py-2 text-slate-700 placeholder-slate-300 focus:border-slate-600 focus:outline-none focus:ring-0 transition-colors" />
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
</div>