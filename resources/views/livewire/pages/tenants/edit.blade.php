<?php

use App\Models\Tenant;
use App\Services\TenantService;
use function Livewire\Volt\{state, rules, mount};

state(['tenant' => null, 'full_name' => '', 'email' => '',
       'phone' => '', 'id_type' => 'national_id',
       'id_number' => '', 'emergency_contact' => '']);

rules([
    'full_name'         => ['required', 'string', 'max:255'],
    'email'             => ['nullable', 'email'],
    'phone'             => ['required', 'string', 'max:20'],
    'id_type'           => ['required', 'string'],
    'id_number'         => ['required', 'string'],
    'emergency_contact' => ['nullable', 'string', 'max:20'],
]);

mount(function (Tenant $tenant) {
    $this->tenant            = $tenant;
    $this->full_name         = $tenant->full_name;
    $this->email             = $tenant->email;
    $this->phone             = $tenant->phone;
    $this->id_type           = $tenant->id_type;
    $this->id_number         = $tenant->id_number;
    $this->emergency_contact = $tenant->emergency_contact;
});

$save = function (TenantService $tenantService) {
    $validated = $this->validate();
    try {
        $tenantService->update($this->tenant, $validated);
        session()->flash('success', 'Tenant updated successfully.');
        $this->redirect(route('tenants.show', $this->tenant), navigate: true);
    } catch (\Exception $e) {
        session()->flash('error', $e->getMessage());
    }
};

?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <a href="{{ route('tenants.show', $tenant) }}" wire:navigate
            class="font-inter text-xs uppercase tracking-widest flex items-center gap-1 mb-4
                transition-opacity hover:opacity-70" style="color:#9BABB3;">
            ← Back to Tenant
        </a>
        <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">
            Edit {{ $tenant->full_name }}
        </h1>
    </div>

    <div class="rounded-xl p-8" style="background-color:#FFFFFF;">
        <form wire:submit="save" class="space-y-6">

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Full Name</label>
                <input wire:model="full_name" type="text"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('full_name') <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Email <span style="color:#C5D0D5;">(optional)</span></label>
                <input wire:model="email" type="email"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
            </div>

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Phone</label>
                <input wire:model="phone" type="tel"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('phone') <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                        style="color:#9BABB3;">ID Type</label>
                    <select wire:model="id_type"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                        style="border-color:#E7EFF3; color:#283439;">
                        <option value="national_id">National ID</option>
                        <option value="passport">Passport</option>
                        <option value="alien_id">Alien ID</option>
                    </select>
                </div>
                <div>
                    <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                        style="color:#9BABB3;">ID Number</label>
                    <input wire:model="id_number" type="text"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                            focus:outline-none focus:ring-0"
                        style="border-color:#E7EFF3; color:#283439;" />
                    @error('id_number') <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Emergency Contact <span style="color:#C5D0D5;">(optional)</span></label>
                <input wire:model="emergency_contact" type="tel"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
            </div>

            <div class="flex items-center justify-between pt-4">
                <a href="{{ route('tenants.show', $tenant) }}" wire:navigate
                    class="font-inter text-sm" style="color:#9BABB3;">Cancel</a>
                <button type="submit"
                    class="font-inter text-xs font-semibold text-white px-6 py-2.5
                        rounded-md transition-opacity hover:opacity-90"
                    style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- Danger Zone --}}
    <div class="mt-6 rounded-xl p-6" style="background-color:#FFFFFF;">
        <p class="font-manrope text-sm font-semibold mb-1" style="color:#9F403D;">Danger Zone</p>
        <p class="font-inter text-xs mb-4" style="color:#9BABB3;">
            Cannot delete a tenant with an active lease. Terminate the lease first.
        </p>
        <form method="POST" action="{{ route('tenants.destroy', $tenant) }}"
            onsubmit="return confirm('Delete {{ $tenant->full_name }}? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="font-inter text-xs font-medium px-4 py-2 rounded-md"
                style="background-color:#FDECEA; color:#9F403D;">
                Delete Tenant
            </button>
        </form>
    </div>
</div>