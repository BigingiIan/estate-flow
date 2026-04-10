<?php

use App\Models\Unit;
use App\Services\UnitService;
use function Livewire\Volt\{state, rules, mount};

state(['unit' => null, 'unit_number' => '', 'base_rent' => '',
       'bedrooms' => 1, 'bathrooms' => 1, 'status' => 'vacant']);

rules([
    'unit_number' => ['required', 'string', 'max:20'],
    'base_rent'   => ['required', 'numeric', 'min:0'],
    'bedrooms'    => ['required', 'integer', 'min:1'],
    'bathrooms'   => ['required', 'integer', 'min:1'],
    'status'      => ['required', 'in:vacant,occupied,maintenance'],
]);

mount(function (Unit $unit) {
    $this->unit        = $unit->load('property');
    $this->unit_number = $unit->unit_number;
    $this->base_rent   = $unit->base_rent;
    $this->bedrooms    = $unit->bedrooms;
    $this->bathrooms   = $unit->bathrooms;
    $this->status      = $unit->status;
});

$save = function (UnitService $unitService) {
    $validated = $this->validate();
    try {
        $unitService->update($this->unit, $validated);
        session()->flash('success', 'Unit updated successfully.');
        $this->redirect(route('properties.show', $this->unit->property), navigate: true);
    } catch (\Exception $e) {
        session()->flash('error', $e->getMessage());
    }
};

?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <a href="{{ route('properties.show', $unit->property) }}" wire:navigate
            class="font-inter text-xs uppercase tracking-widest flex items-center gap-1 mb-4
                transition-opacity hover:opacity-70" style="color:#9BABB3;">
            ← Back to {{ $unit->property->name }}
        </a>
        <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">
            Edit Unit {{ $unit->unit_number }}
        </h1>
        <p class="font-inter text-sm mt-1" style="color:#9BABB3;">{{ $unit->property->name }}</p>
    </div>

    <div class="rounded-xl p-8" style="background-color:#FFFFFF;">
        <form wire:submit="save" class="space-y-6">

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Unit Number</label>
                <input wire:model="unit_number" type="text"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('unit_number') <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Base Rent (KES)</label>
                <input wire:model="base_rent" type="number"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('base_rent') <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                        style="color:#9BABB3;">Bedrooms</label>
                    <select wire:model="bedrooms"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                        style="border-color:#E7EFF3; color:#283439;">
                        @foreach(range(1, 6) as $n)
                            <option value="{{ $n }}">{{ $n }} Bedroom{{ $n > 1 ? 's' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                        style="color:#9BABB3;">Bathrooms</label>
                    <select wire:model="bathrooms"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                        style="border-color:#E7EFF3; color:#283439;">
                        @foreach(range(1, 4) as $n)
                            <option value="{{ $n }}">{{ $n }} Bathroom{{ $n > 1 ? 's' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Status</label>
                <select wire:model="status"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                    style="border-color:#E7EFF3; color:#283439;">
                    <option value="vacant">Vacant</option>
                    <option value="occupied">Occupied</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>

            <div class="flex items-center justify-between pt-4">
                <a href="{{ route('properties.show', $unit->property) }}" wire:navigate
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
            Cannot delete an occupied unit. Terminate the lease first.
        </p>
        <form method="POST" action="{{ route('units.destroy', $unit) }}"
            onsubmit="return confirm('Delete Unit {{ $unit->unit_number }}? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="font-inter text-xs font-medium px-4 py-2 rounded-md"
                style="background-color:#FDECEA; color:#9F403D;">
                Delete Unit
            </button>
        </form>
    </div>
</div>