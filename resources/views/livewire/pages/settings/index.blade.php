<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use function Livewire\Volt\{state, mount};

state([
    'show_sandbox_banner' => true,
    'compact_dashboard'   => false,
    'demo_sms_mode'       => true,
    'currency'            => 'KES',
    'date_format'         => 'd M Y',
    'saved'               => false,
]);

mount(function () {
    $prefs = session('estateflow_prefs', []);
    $this->show_sandbox_banner = $prefs['show_sandbox_banner'] ?? true;
    $this->compact_dashboard   = $prefs['compact_dashboard']   ?? false;
    $this->demo_sms_mode       = $prefs['demo_sms_mode']       ?? true;
    $this->currency            = $prefs['currency']            ?? 'KES';
    $this->date_format         = $prefs['date_format']         ?? 'd M Y';
});

$saveSettings = function () {
    session(['estateflow_prefs' => [
        'show_sandbox_banner' => $this->show_sandbox_banner,
        'compact_dashboard'   => $this->compact_dashboard,
        'demo_sms_mode'       => $this->demo_sms_mode,
        'currency'            => $this->currency,
        'date_format'         => $this->date_format,
    ]]);
    $this->saved = true;
    $this->dispatch('settings-saved');
};

$runReminders = function () {
    Artisan::call('reminders:send-rent');
    session()->flash('success', 'Rent reminders sent successfully.');
};

$resetSandbox = function () {
    Artisan::call('migrate:fresh', ['--seed' => true]);
    session()->flash('success', 'Sandbox reset and reseeded successfully.');
    $this->redirect(route('dashboard'), navigate: true);
};

?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="mb-8">
        <p class="font-inter text-xs font-medium uppercase tracking-widest"
            style="color:#9BABB3; letter-spacing:0.08em;">
            Personal preferences and sandbox controls
        </p>
        <h1 class="font-manrope text-2xl font-semibold mt-1" style="color:#283439;">
            Dashboard &amp; Sandbox Settings
        </h1>
    </div>

    @if($saved)
    <div class="mb-6 px-4 py-3 rounded-md font-inter text-sm"
        style="background-color:#E7EFF3; color:#585E6C;"
        x-data x-init="setTimeout(() => $el.remove(), 3000)">
        Settings saved.
    </div>
    @endif

    {{-- Main settings card --}}
    <div class="rounded-xl p-8 mb-6" style="background-color:#FFFFFF;">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-10">

            {{-- Left: Display --}}
            <div>
                <p class="font-inter text-xs font-semibold uppercase tracking-widest mb-5"
                    style="color:#9BABB3; letter-spacing:0.1em;">Display</p>

                {{-- Show sandbox banner --}}
                <div class="flex items-start justify-between mb-5">
                    <div>
                        <p class="font-inter text-sm font-medium" style="color:#283439;">
                            Show sandbox banner
                        </p>
                        <p class="font-inter text-xs mt-0.5" style="color:#9BABB3;">
                            The yellow notice at the top of the dashboard
                        </p>
                    </div>
                    <button wire:click="$toggle('show_sandbox_banner')"
                        class="relative inline-flex h-5 w-9 items-center rounded-full
                            transition-colors focus:outline-none flex-shrink-0 ml-4"
                        style="background-color: {{ $show_sandbox_banner ? '#585E6C' : '#E7EFF3' }};">
                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white
                            transition-transform shadow-sm"
                            style="transform: translateX({{ $show_sandbox_banner ? '18px' : '2px' }});">
                        </span>
                    </button>
                </div>

                {{-- Compact dashboard --}}
                <div class="flex items-start justify-between mb-8">
                    <div>
                        <p class="font-inter text-sm font-medium" style="color:#283439;">
                            Compact dashboard
                        </p>
                        <p class="font-inter text-xs mt-0.5" style="color:#9BABB3;">
                            Tighter cards, smaller numbers
                        </p>
                    </div>
                    <button wire:click="$toggle('compact_dashboard')"
                        class="relative inline-flex h-5 w-9 items-center rounded-full
                            transition-colors focus:outline-none flex-shrink-0 ml-4"
                        style="background-color: {{ $compact_dashboard ? '#585E6C' : '#E7EFF3' }};">
                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white
                            transition-transform shadow-sm"
                            style="transform: translateX({{ $compact_dashboard ? '18px' : '2px' }});">
                        </span>
                    </button>
                </div>

                {{-- Currency --}}
                <div class="mb-5">
                    <p class="font-inter text-xs font-semibold uppercase tracking-widest mb-2"
                        style="color:#9BABB3; letter-spacing:0.1em;">Currency</p>
                    <select wire:model="currency"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                            focus:outline-none focus:ring-0"
                        style="border-color:#E7EFF3; color:#283439;">
                        <option value="KES">KES — Kenyan Shilling</option>
                        <option value="USD">USD — US Dollar</option>
                        <option value="GBP">GBP — British Pound</option>
                        <option value="EUR">EUR — Euro</option>
                    </select>
                </div>

                {{-- Date format --}}
                <div>
                    <p class="font-inter text-xs font-semibold uppercase tracking-widest mb-2"
                        style="color:#9BABB3; letter-spacing:0.1em;">Date Format</p>
                    <select wire:model="date_format"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                            focus:outline-none focus:ring-0"
                        style="border-color:#E7EFF3; color:#283439;">
                        <option value="d M Y">{{ now()->format('d M Y') }}</option>
                        <option value="d/m/Y">{{ now()->format('d/m/Y') }}</option>
                        <option value="Y-m-d">{{ now()->format('Y-m-d') }}</option>
                        <option value="M d, Y">{{ now()->format('M d, Y') }}</option>
                    </select>
                </div>
            </div>

            {{-- Right: Sandbox behaviour --}}
            <div>
                <p class="font-inter text-xs font-semibold uppercase tracking-widest mb-5"
                    style="color:#9BABB3; letter-spacing:0.1em;">Sandbox Behaviour</p>

                {{-- Demo SMS mode --}}
                <div class="flex items-start justify-between mb-8">
                    <div>
                        <p class="font-inter text-sm font-medium" style="color:#283439;">
                            Demo SMS mode
                        </p>
                        <p class="font-inter text-xs mt-0.5" style="color:#9BABB3;">
                            Log SMS to file instead of charging Africa's Talking
                        </p>
                    </div>
                    <button wire:click="$toggle('demo_sms_mode')"
                        class="relative inline-flex h-5 w-9 items-center rounded-full
                            transition-colors focus:outline-none flex-shrink-0 ml-4"
                        style="background-color: {{ $demo_sms_mode ? '#585E6C' : '#E7EFF3' }};">
                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white
                            transition-transform shadow-sm"
                            style="transform: translateX({{ $demo_sms_mode ? '18px' : '2px' }});">
                        </span>
                    </button>
                </div>

                {{-- Save button --}}
                <button wire:click="saveSettings"
                    class="font-inter text-xs font-semibold text-white px-6 py-2.5
                        rounded-md transition-opacity hover:opacity-90"
                    style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                    Save Settings
                </button>
            </div>

        </div>
    </div>

    {{-- Action buttons --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        {{-- Run reminders --}}
        <div class="rounded-xl p-6 text-center" style="background-color:#F0FDF4;">
            <button wire:click="runReminders"
                wire:confirm="This will send SMS reminders to all tenants with unpaid rent. Continue?"
                class="font-inter text-sm font-semibold transition-opacity hover:opacity-80"
                style="color:#22863a;">
                Run rent reminders now
            </button>
            <p class="font-inter text-xs mt-1" style="color:#9BABB3;">
                Triggers the same job the scheduler runs at 9am daily.
            </p>
        </div>

        {{-- Reset sandbox --}}
        <div class="rounded-xl p-6 text-center" style="background-color:#FDECEA;">
            <button wire:click="resetSandbox"
                wire:confirm="This will DROP every table and reload the demo dataset. All your data will be lost. Are you sure?"
                class="font-inter text-sm font-semibold transition-opacity hover:opacity-80"
                style="color:#9F403D;">
                Reset &amp; reseed sandbox
            </button>
            <p class="font-inter text-xs mt-1" style="color:#9BABB3;">
                Drops every table and reloads the demo dataset.
            </p>
        </div>

    </div>

    {{-- M-Pesa reference section --}}
    <div class="rounded-xl p-8 mt-6" style="background-color:#FFFFFF;">
        <p class="font-manrope text-sm font-semibold mb-1" style="color:#283439;">
            Payment Integration
        </p>
        <p class="font-inter text-xs mb-5" style="color:#9BABB3;">
            Current payment capture method. M-Pesa STK Push requires a public server URL and will be enabled post-deployment.
        </p>

        <div class="space-y-4">

            {{-- M-Pesa manual (active) --}}
            <div class="flex items-center justify-between p-4 rounded-lg"
                style="background-color:#E7EFF3;">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-md flex items-center justify-center"
                        style="background-color:#585E6C;">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-inter text-sm font-medium" style="color:#283439;">
                            M-Pesa — Manual reference entry
                        </p>
                        <p class="font-inter text-xs" style="color:#9BABB3;">
                            Tenant pays normally, landlord records the confirmation code
                        </p>
                    </div>
                </div>
                <span class="font-inter text-xs font-medium px-3 py-1 rounded-full"
                    style="background-color:#585E6C; color:#FFFFFF;">
                    Active
                </span>
            </div>

            {{-- M-Pesa STK Push (coming soon) --}}
            <div class="flex items-center justify-between p-4 rounded-lg"
                style="background-color:#F7FAFC;">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-md flex items-center justify-center"
                        style="background-color:#E7EFF3;">
                        <svg class="w-4 h-4" style="color:#9BABB3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-inter text-sm font-medium" style="color:#9BABB3;">
                            M-Pesa STK Push — Automatic
                        </p>
                        <p class="font-inter text-xs" style="color:#9BABB3;">
                            Push payment prompt to tenant's phone. Requires public server URL.
                        </p>
                    </div>
                </div>
                <span class="font-inter text-xs font-medium px-3 py-1 rounded-full"
                    style="background-color:#EFF4F7; color:#9BABB3;">
                    Post-deployment
                </span>
            </div>

            {{-- Flutterwave (coming soon) --}}
            <div class="flex items-center justify-between p-4 rounded-lg"
                style="background-color:#F7FAFC;">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-md flex items-center justify-center"
                        style="background-color:#E7EFF3;">
                        <svg class="w-4 h-4" style="color:#9BABB3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-inter text-sm font-medium" style="color:#9BABB3;">
                            Flutterwave — Card &amp; mobile money
                        </p>
                        <p class="font-inter text-xs" style="color:#9BABB3;">
                            KES card payments and mobile money. Requires public server URL.
                        </p>
                    </div>
                </div>
                <span class="font-inter text-xs font-medium px-3 py-1 rounded-full"
                    style="background-color:#EFF4F7; color:#9BABB3;">
                    Post-deployment
                </span>
            </div>

        </div>
    </div>

</div>