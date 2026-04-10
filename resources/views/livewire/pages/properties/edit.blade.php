<?php

use App\Models\Property;
use App\Services\PropertyService;
use function Livewire\Volt\{state, rules, mount};

state(['property' => null, 'name' => '', 'location' => '', 'description' => '']);

rules([
    'name'        => ['required', 'string', 'max:255'],
    'location'    => ['required', 'string', 'max:255'],
    'description' => ['nullable', 'string'],
]);

mount(function (Property $property) {
    $this->property    = $property;
    $this->name        = $property->name;
    $this->location    = $property->location;
    $this->description = $property->description;
});

$save = function (PropertyService $propertyService) {
    $validated = $this->validate();
    try {
        $propertyService->update($this->property, $validated);
        session()->flash('success', 'Property updated successfully.');
        $this->redirect(route('properties.show', $this->property), navigate: true);
    } catch (\Exception $e) {
        session()->flash('error', $e->getMessage());
    }
};

?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <a href="{{ route('properties.show', $property) }}" wire:navigate
            class="font-inter text-xs uppercase tracking-widest flex items-center gap-1 mb-4
                transition-opacity hover:opacity-70" style="color:#9BABB3;">
            ← Back to Property
        </a>
        <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">Edit Property</h1>
        <p class="font-inter text-sm mt-1" style="color:#9BABB3;">{{ $property->location }}</p>
    </div>

    <div class="rounded-xl p-8" style="background-color:#FFFFFF;">
        <form wire:submit="save" class="space-y-6">
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Property Name</label>
                <input wire:model="name" type="text"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0 transition-colors"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('name') <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Location</label>
                <input wire:model="location" type="text"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0 transition-colors"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('location') <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Description <span style="color:#C5D0D5;">(optional)</span></label>
                <textarea wire:model="description" rows="3"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0 resize-none"
                    style="border-color:#E7EFF3; color:#283439;"></textarea>
            </div>

            <div class="flex items-center justify-between pt-4">
                <a href="{{ route('properties.show', $property) }}" wire:navigate
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
            Deleting a property removes all its units. This cannot be undone if no active leases exist.
        </p>
        <form method="POST" action="{{ route('properties.destroy', $property) }}"
            onsubmit="return confirm('Delete {{ $property->name }}? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="font-inter text-xs font-medium px-4 py-2 rounded-md"
                style="background-color:#FDECEA; color:#9F403D;">
                Delete Property
            </button>
        </form>
    </div>
</div>