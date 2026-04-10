<?php

use App\Models\Property;
use App\Models\Unit;
use function Livewire\Volt\{state, rules, mount};

state([
    'property'    => null,
    'unit_number' => '',
    'base_rent'   => '',
    'bedrooms'    => 1,
    'bathrooms'   => 1,
    'status'      => 'vacant',
]);

rules([
    'unit_number' => ['required', 'string', 'max:20'],
    'base_rent'   => ['required', 'numeric', 'min:0'],
    'bedrooms'    => ['required', 'integer', 'min:1'],
    'bathrooms'   => ['required', 'integer', 'min:1'],
    'status'      => ['required', 'in:vacant,occupied,maintenance'],
]);

mount(function (Property $property) {
    $this->property = $property;
});

$save = function () {
    $validated = $this->validate();

    // Check unit number is unique within this property
    $exists = Unit::where('property_id', $this->property->id)
        ->where('unit_number', $validated['unit_number'])
        ->exists();

    if ($exists) {
        $this->addError('unit_number', 'This unit number already exists in this property.');
        return;
    }

    Unit::create([
        'property_id' => $this->property->id,
        'unit_number' => $validated['unit_number'],
        'base_rent'   => $validated['base_rent'],
        'bedrooms'    => $validated['bedrooms'],
        'bathrooms'   => $validated['bathrooms'],
        'status'      => $validated['status'],
    ]);

    session()->flash('success', 'Unit added successfully.');
    $this->redirect(route('properties.show', $this->property), navigate: true);
};

?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <a href="{{ route('properties.show', $property) }}" wire:navigate
            class="font-inter text-xs uppercase tracking-widest flex items-center gap-1 mb-4
                transition-opacity hover:opacity-70"
            style="color:#9BABB3;">
            ← Back to {{ $property->name }}
        </a>
        <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">
            Add Unit to {{ $property->name }}
        </h1>
        <p class="font-inter text-sm mt-1" style="color:#9BABB3;">
            {{ $property->location }}
        </p>
    </div>

    <div class="rounded-xl p-8" style="background-color:#FFFFFF;">
        <form wire:submit="save" class="space-y-6">

            {{-- Unit Number --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Unit Number</label>
                <input wire:model="unit_number" type="text" placeholder="e.g. A1, B12, PH1"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0 transition-colors"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('unit_number')
                    <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Base Rent --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Base Rent (KES)</label>
                <input wire:model="base_rent" type="number" placeholder="35000"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0 transition-colors"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('base_rent')
                    <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Bedrooms + Bathrooms --}}
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                        style="color:#9BABB3;">Bedrooms</label>
                    <select wire:model="bedrooms"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                            focus:outline-none focus:ring-0"
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
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                            focus:outline-none focus:ring-0"
                        style="border-color:#E7EFF3; color:#283439;">
                        @foreach(range(1, 4) as $n)
                            <option value="{{ $n }}">{{ $n }} Bathroom{{ $n > 1 ? 's' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Status --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Status</label>
                <select wire:model="status"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;">
                    <option value="vacant">Vacant</option>
                    <option value="occupied">Occupied</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between pt-4">
                <a href="{{ route('properties.show', $property) }}" wire:navigate
                    class="font-inter text-sm" style="color:#9BABB3;">Cancel</a>
                <button type="submit"
                    class="font-inter text-xs font-semibold text-white px-6 py-2.5
                        rounded-md transition-opacity hover:opacity-90"
                    style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                    Save Unit
                </button>
            </div>

        </form>
    </div>

</div>