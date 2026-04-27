<?php

use App\Models\Property;
use App\Models\Unit;
use function Livewire\Volt\{state, rules, mount};
use App\Services\UnitService;

state([
    'property'    => null,
    'unit_number' => '',
    'base_rent'   => '',
    'bedrooms'    => 1,
    'bathrooms'   => 1,
    'status'      => 'vacant',
    'unit_type'      => 'apartment',
    'size_sqft'      => '',
    'rate_per_sqft'  => '',
    'floor'          => '',
    'is_furnished'   => false,
    'service_charge' => '',
]);

rules([
    'unit_number' => ['required', 'string', 'max:20'],
    'base_rent'   => ['required', 'numeric', 'min:0'],
    'bedrooms'    => ['required', 'integer', 'min:1'],
    'bathrooms'   => ['required', 'integer', 'min:1'],
    'status'      => ['required', 'in:vacant,occupied,maintenance'],
]);

$updatedSizeSqft = function () {
    if ($this->size_sqft && $this->rate_per_sqft) {
        $this->base_rent = $this->size_sqft * $this->rate_per_sqft;
    }
};

$updatedRatePerSqft = function () {
    if ($this->size_sqft && $this->rate_per_sqft) {
        $this->base_rent = $this->size_sqft * $this->rate_per_sqft;
    }
};

mount(function (Property $property) {
    $this->property = $property;
});

$save = function (UnitService $unitService) {
    $validated = $this->validate();

    try {
        // Option 1: Using UnitService (recommended if you have business logic)
        $unitService->create($this->property, $validated);
        
        // Option 2: Direct creation (uncomment this and comment the above if you don't need UnitService)
        // Unit::create([
        //     'property_id' => $this->property->id,
        //     'unit_number' => $validated['unit_number'],
        //     'base_rent'   => $validated['base_rent'],
        //     'bedrooms'    => $validated['bedrooms'],
        //     'bathrooms'   => $validated['bathrooms'],
        //     'status'      => $validated['status'],
        // ]);
        
        session()->flash('success', 'Unit added successfully.');
        $this->redirect(route('properties.show', $this->property), navigate: true);
    } catch (\Exception $e) {
        $this->addError('unit_number', $e->getMessage());
    }
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
                <input wire:model="base_rent" type="number" step="0.01" placeholder="35000"
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

            {{-- Unit type --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Unit Type</label>
                <select wire:model.live="unit_type"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                    style="border-color:#E7EFF3; color:#283439;">
                    <option value="apartment">Apartment</option>
                    <option value="office">Office</option>
                    <option value="retail">Retail / Shop</option>
                    <option value="warehouse">Warehouse</option>
                    <option value="studio">Studio</option>
                </select>
            </div>

            {{-- Commercial fields — only show for non-apartment types --}}
            @if(in_array($unit_type ?? 'apartment', ['office', 'retail', 'warehouse']))
                <div class="grid grid-cols-2 gap-6 p-4 rounded-lg" style="background-color:#EFF4F7;">
                    <div>
                        <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                            style="color:#9BABB3;">Size (sq ft)</label>
                        <input wire:model.live="size_sqft" type="number" placeholder="e.g. 1200"
                            class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                            style="border-color:#E7EFF3; color:#283439;" />
                        <p class="font-inter text-xs mt-1" style="color:#9BABB3;">
                            Commercial rent = size × rate per sqft
                        </p>
                    </div>
                    <div>
                        <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                            style="color:#9BABB3;">Rate per sq ft (KES)</label>
                        <input wire:model.live="rate_per_sqft" type="number" placeholder="e.g. 103"
                            class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                            style="border-color:#E7EFF3; color:#283439;" />
                    </div>
                    <div>
                        <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                            style="color:#9BABB3;">Floor</label>
                        <input wire:model="floor" type="text" placeholder="e.g. 3rd Floor, Ground"
                            class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                            style="border-color:#E7EFF3; color:#283439;" />
                    </div>
                    <div>
                        <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                            style="color:#9BABB3;">Monthly Service Charge (KES)</label>
                        <input wire:model="service_charge" type="number" placeholder="e.g. 5000"
                            class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                            style="border-color:#E7EFF3; color:#283439;" />
                    </div>
                </div>

                {{-- Auto-calculated rent --}}
                @if($size_sqft && $rate_per_sqft)
                    <div class="px-4 py-3 rounded-md" style="background-color:#E7EFF3;">
                        <p class="font-inter text-xs" style="color:#585E6C;">
                            Calculated base rent:
                            <span class="font-manrope font-bold">
                                KES {{ number_format($size_sqft * $rate_per_sqft, 0) }}/month
                            </span>
                            ({{ number_format($size_sqft, 0) }} sqft × KES {{ number_format($rate_per_sqft, 0) }}/sqft)
                        </p>
                    </div>
                @endif
            @endif

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