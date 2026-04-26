<?php

use App\Livewire\Actions\Logout;

$logout = function (Logout $logout) {
    $logout();
    $this->redirect('/', navigate: true);
};

?>

<nav x-data="{ open: false }" style="background-color: #FFFFFF;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            {{-- Left: Logo + Nav Links --}}
            <div class="flex items-center gap-10">
                <a href="{{ route('dashboard') }}" wire:navigate
                    class="font-manrope text-lg font-bold"
                    style="color: #283439; letter-spacing: -0.02em;">
                    EstateFlow
                </a>

                <div class="hidden sm:flex items-center gap-7">
                    <a href="{{ route('dashboard') }}" wire:navigate
                        class="font-inter text-sm font-medium pb-0.5 transition-colors
                        {{ request()->routeIs('dashboard')
                            ? 'border-b-2 font-semibold'
                            : 'hover:opacity-70' }}"
                        style="{{ request()->routeIs('dashboard')
                            ? 'color:#283439; border-color:#585E6C;'
                            : 'color:#6B7A82;' }}">
                        Home
                    </a>
                    <a href="{{ route('properties.index') }}" wire:navigate
                        class="font-inter text-sm font-medium pb-0.5 transition-colors
                        {{ request()->routeIs('properties.*')
                            ? 'border-b-2 font-semibold'
                            : 'hover:opacity-70' }}"
                        style="{{ request()->routeIs('properties.*')
                            ? 'color:#283439; border-color:#585E6C;'
                            : 'color:#6B7A82;' }}">
                        Properties
                    </a>
                    <a href="{{ route('tenants.index') }}" wire:navigate
                        class="font-inter text-sm font-medium pb-0.5 transition-colors
                        {{ request()->routeIs('tenants.*')
                            ? 'border-b-2 font-semibold'
                            : 'hover:opacity-70' }}"
                        style="{{ request()->routeIs('tenants.*')
                            ? 'color:#283439; border-color:#585E6C;'
                            : 'color:#6B7A82;' }}">
                        Tenants
                    </a>
                    <a href="{{ route('leases.index') }}" wire:navigate
                        class="font-inter text-sm font-medium pb-0.5 transition-colors
                        {{ request()->routeIs('leases.*')
                            ? 'border-b-2 font-semibold'
                            : 'hover:opacity-70' }}"
                        style="{{ request()->routeIs('leases.*')
                            ? 'color:#283439; border-color:#585E6C;'
                            : 'color:#6B7A82;' }}">
                        Leases
                    </a>
                    <a href="{{ route('reports.index') }}" wire:navigate
                        class="font-inter text-sm font-medium pb-0.5 transition-colors
                        {{ request()->routeIs('reports.*')
                            ? 'border-b-2 font-semibold'
                            : 'hover:opacity-70' }}"
                        style="{{ request()->routeIs('reports.*')
                            ? 'color:#283439; border-color:#585E6C;'
                            : 'color:#6B7A82;' }}">
                        Reports
                    </a>
                </div>
            </div>

            {{-- Right: Search + Icons + Avatar --}}
            <div class="hidden sm:flex items-center gap-3">

                {{-- Search -- }}
                /*<div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4"
                        style="color:#9BABB3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" placeholder="Search..."
                        class="font-inter text-sm pl-9 pr-4 py-1.5 rounded-md focus:outline-none w-44"
                        style="background-color:#EFF4F7; color:#283439;" />
                </div> 
                */

                {{-- Notification bell --}}
                <button class="p-1.5 rounded-md transition-colors hover:opacity-70"
                    style="color:#6B7A82;">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </button>

                {{-- Settings gear → now links to settings page --}}
                <a href="{{ route('settings') }}" wire:navigate
                    class="p-1.5 rounded-md transition-colors hover:opacity-70"
                    style="color: {{ request()->routeIs('settings') ? '#283439' : '#6B7A82' }};">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </a>

                {{-- User avatar dropdown --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center justify-center w-8 h-8 rounded-full
                            font-manrope text-sm font-bold text-white focus:outline-none"
                            style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="px-4 py-3" style="border-bottom: 1px solid #EFF4F7;">
                            <p class="font-inter text-sm font-semibold" style="color:#283439;">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="font-inter text-xs mt-0.5" style="color:#9BABB3;">
                                {{ auth()->user()->email }}
                            </p>
                        </div>
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>
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
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md transition duration-150"
                    style="color:#9BABB3;">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open}"
                            class="inline-flex" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open}"
                            class="hidden" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

        </div>
    </div>

    {{-- Mobile menu --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden"
        style="background-color:#F7FAFC;">
        <div class="pt-2 pb-3 space-y-1 px-4">
            <a href="{{ route('dashboard') }}" wire:navigate
                class="block py-2 font-inter text-sm" style="color:#283439;">Home</a>
            <a href="{{ route('properties.index') }}" wire:navigate
                class="block py-2 font-inter text-sm" style="color:#283439;">Properties</a>
            <a href="{{ route('tenants.index') }}" wire:navigate
                class="block py-2 font-inter text-sm" style="color:#283439;">Tenants</a>
            <a href="{{ route('leases.index') }}" wire:navigate
                class="block py-2 font-inter text-sm" style="color:#283439;">Leases</a>
            <a href="{{ route('reports.index') }}" wire:navigate
                class="block py-2 font-inter text-sm" style="color:#283439;">Reports</a>
        </div>
        <div class="pt-4 pb-3 px-4" style="border-top: 1px solid #E7EFF3;">
            <p class="font-inter text-sm font-semibold" style="color:#283439;">
                {{ auth()->user()->name }}
            </p>
            <p class="font-inter text-xs mt-0.5" style="color:#9BABB3;">
                {{ auth()->user()->email }}
            </p>
            <button wire:click="logout"
                class="mt-3 font-inter text-sm" style="color:#6B7A82;">
                Log Out
            </button>
        </div>
    </div>
</nav>