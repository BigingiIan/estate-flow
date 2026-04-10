<?php

use App\Models\Property;
use function Livewire\Volt\{state, rules};
use App\Services\PropertyService;

state([
    'name'        => '',
    'location'    => '',
    'description' => '',
]);

rules([
    'name'        => ['required', 'string', 'max:255'],
    'location'    => ['required', 'string', 'max:255'],
    'description' => ['nullable', 'string'],
]);

$save = function (PropertyService $propertyService) {
    $validated = $this->validate();

    try {
        $propertyService->create($validated);
        session()->flash('success', 'Property added successfully.');
        $this->redirect(route('properties.index'), navigate: true);
    } catch (\Exception $e) {
        session()->flash('error', $e->getMessage());
    }
};

?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="mb-8">
        <a href="{{ route('properties.index') }}" wire:navigate
            class="font-inter text-xs uppercase tracking-widest flex items-center gap-1 mb-4
                transition-opacity hover:opacity-70"
            style="color:#9BABB3;">
            ← Back to Properties
        </a>
        <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">
            Add New Property
        </h1>
        <p class="font-inter text-sm mt-1" style="color:#9BABB3;">
            Enter the property details below.
        </p>
    </div>

    {{-- Form Card --}}
    <div class="rounded-xl p-8" style="background-color:#FFFFFF;">
        <form wire:submit="save" class="space-y-6">

            {{-- Property Name --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">
                    Property Name
                </label>
                <input wire:model="name"
                    type="text"
                    placeholder="e.g. Sunshine Apartments"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0 transition-colors"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('name')
                    <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Location --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">
                    Location
                </label>
                <input wire:model="location"
                    type="text"
                    placeholder="e.g. Westlands, Nairobi"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0 transition-colors"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('location')
                    <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">
                    Description <span style="color:#C5D0D5;">(optional)</span>
                </label>
                <textarea wire:model="description"
                    rows="3"
                    placeholder="Brief description of the property..."
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0 transition-colors resize-none"
                    style="border-color:#E7EFF3; color:#283439;"></textarea>
                @error('description')
                    <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between pt-4">
                <a href="{{ route('properties.index') }}" wire:navigate
                    class="font-inter text-sm" style="color:#9BABB3;">
                    Cancel
                </a>
                <button type="submit"
                    class="font-inter text-xs font-semibold text-white px-6 py-2.5
                        rounded-md transition-opacity hover:opacity-90"
                    style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                    Save Property
                </button>
            </div>

        </form>
    </div>

</div>
