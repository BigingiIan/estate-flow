<?php

use App\Models\Tenant;
use function Livewire\Volt\{state, rules};
use App\Services\TenantService;

state([
    'full_name'         => '',
    'email'             => '',
    'phone'             => '',
    'id_type'           => 'national_id',
    'id_number'         => '',
    'emergency_contact' => '',
]);

rules([
    'full_name'         => ['required', 'string', 'max:255'],
    'email'             => ['nullable', 'email', 'unique:tenants,email'],
    'phone'             => ['required', 'string', 'max:20'],
    'id_type'           => ['required', 'string'],
    'id_number'         => ['required', 'string', 'unique:tenants,id_number'],
    'emergency_contact' => ['nullable', 'string', 'max:20'],
]);

$save = function (TenantService $tenantService) {
    $validated = $this->validate();

    try {
        $tenantService->create($validated);
        session()->flash('success', 'Tenant added successfully.');
        $this->redirect(route('tenants.index'), navigate: true);
    } catch (\Exception $e) {
        session()->flash('error', $e->getMessage());
    }
};

?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <a href="{{ route('tenants.index') }}" wire:navigate
            class="font-inter text-xs uppercase tracking-widest flex items-center gap-1 mb-4
                transition-opacity hover:opacity-70"
            style="color:#9BABB3;">
            ← Back to Tenants
        </a>
        <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">
            Add New Tenant
        </h1>
        <p class="font-inter text-sm mt-1" style="color:#9BABB3;">
            Enter the tenant's details below.
        </p>
    </div>

    <div class="rounded-xl p-8" style="background-color:#FFFFFF;">
        <form wire:submit="save" class="space-y-6">

            {{-- Full Name --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Full Name</label>
                <input wire:model="full_name" type="text" placeholder="John Ndegwa"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0 transition-colors"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('full_name')
                    <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Email Address <span style="color:#C5D0D5;">(optional)</span></label>
                <input wire:model="email" type="email" placeholder="john@example.com"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0 transition-colors"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('email')
                    <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Phone --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Phone Number</label>
                <input wire:model="phone" type="tel" placeholder="+254700000000"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0 transition-colors"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('phone')
                    <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                @enderror
            </div>

            {{-- ID Type + ID Number --}}
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                        style="color:#9BABB3;">ID Type</label>
                    <select wire:model="id_type"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                            focus:outline-none focus:ring-0"
                        style="border-color:#E7EFF3; color:#283439;">
                        <option value="national_id">National ID</option>
                        <option value="passport">Passport</option>
                        <option value="alien_id">Alien ID</option>
                    </select>
                </div>
                <div>
                    <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                        style="color:#9BABB3;">ID Number</label>
                    <input wire:model="id_number" type="text" placeholder="12345678"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                            focus:outline-none focus:ring-0 transition-colors"
                        style="border-color:#E7EFF3; color:#283439;" />
                    @error('id_number')
                        <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Emergency Contact --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Emergency Contact <span style="color:#C5D0D5;">(optional)</span></label>
                <input wire:model="emergency_contact" type="tel" placeholder="+254700000001"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0 transition-colors"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('emergency_contact')
                    <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between pt-4">
                <a href="{{ route('tenants.index') }}" wire:navigate
                    class="font-inter text-sm" style="color:#9BABB3;">Cancel</a>
                <button type="submit"
                    class="font-inter text-xs font-semibold text-white px-6 py-2.5
                        rounded-md transition-opacity hover:opacity-90"
                    style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                    Save Tenant
                </button>
            </div>

        </form>
    </div>

</div>