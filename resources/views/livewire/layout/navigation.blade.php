<?php

use App\Livewire\Actions\Logout;

$logout = function (Logout $logout) {
    $logout();
    $this->redirect('/', navigate: true);
};

?>

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- Left: Logo + Nav Links --}}
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" wire:navigate
                    class="text-lg font-bold text-slate-700 tracking-tight">
                    EstateFlow
                </a>

                <div class="hidden sm:flex items-center gap-6">
                    <a href="{{ route('dashboard') }}" wire:navigate
                        class="text-sm {{ request()->routeIs('dashboard') ? 'text-slate-800 border-b-2 border-slate-700 pb-0.5 font-medium' : 'text-slate-500 hover:text-slate-700' }} transition-colors">
                        Home
                    </a>
                    <a href="{{ route('properties.index') }}" wire:navigate
                        class="text-sm {{ request()->routeIs('properties.*') ? 'text-slate-800 border-b-2 border-slate-700 pb-0.5 font-medium' : 'text-slate-500 hover:text-slate-700' }} transition-colors">
                        Properties
                    </a>
                    <a href="{{ route('tenants.index') }}" wire:navigate
                        class="text-sm {{ request()->routeIs('tenants.*') ? 'text-slate-800 border-b-2 border-slate-700 pb-0.5 font-medium' : 'text-slate-500 hover:text-slate-700' }} transition-colors">
                        Tenants
                    </a>
                    <a href="{{ route('leases.index') }}" wire:navigate
                        class="text-sm {{ request()->routeIs('leases.*') ? 'text-slate-800 border-b-2 border-slate-700 pb-0.5 font-medium' : 'text-slate-500 hover:text-slate-700' }} transition-colors">
                        Leases
                    </a>
                </div>
            </div>

            {{-- Right: Search + Icons + User --}}
            <div class="hidden sm:flex items-center gap-4">

                {{-- Search --}}
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" placeholder="Search..."
                        class="pl-9 pr-4 py-1.5 text-sm bg-gray-50 border border-gray-200 rounded-md text-slate-600 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300 w-48" />
                </div>

                {{-- Notification bell --}}
                <button class="p-1.5 text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </button>

                {{-- Settings --}}
                <button class="p-1.5 text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </button>

                {{-- User avatar with dropdown --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center justify-center w-8 h-8 rounded-full bg-slate-600 text-white text-sm font-semibold focus:outline-none">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <p class="text-sm font-medium text-slate-700">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-400">{{ auth()->user()->email }}</p>
                        </div>
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>

            </div>

            {{-- Mobile hamburger --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

        </div>
    </div>

    {{-- Mobile menu --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1 px-4">
            <a href="{{ route('dashboard') }}" wire:navigate class="block py-2 text-sm text-slate-600">Home</a>
            <a href="{{ route('properties.index') }}" wire:navigate class="block py-2 text-sm text-slate-600">Properties</a>
            <a href="{{ route('tenants.index') }}" wire:navigate class="block py-2 text-sm text-slate-600">Tenants</a>
            <a href="{{ route('leases.index') }}" wire:navigate class="block py-2 text-sm text-slate-600">Leases</a>
        </div>
        <div class="pt-4 pb-1 border-t border-gray-200 px-4">
            <p class="text-sm font-medium text-slate-700">{{ auth()->user()->name }}</p>
            <p class="text-xs text-slate-400">{{ auth()->user()->email }}</p>
            <button wire:click="logout" class="mt-3 text-sm text-slate-500 hover:text-slate-700">
                Log Out
            </button>
        </div>
    </div>
</nav>