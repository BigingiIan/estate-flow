<?php

use App\Models\Unit;
use App\Models\Property;
use function Livewire\Volt\{state, computed};

state(['search' => '', 'status' => '', 'property_id' => '']);

$units = computed(function () {
    $propertyIds = Property::pluck('id');

    return Unit::whereIn('property_id', $propertyIds)
        ->with(['property', 'leases' => fn($q) => $q->where('status', 'active')->with('tenant')])
        ->when($this->status, fn($q) => $q->where('status', $this->status))
        ->when($this->property_id, fn($q) => $q->where('property_id', $this->property_id))
        ->when($this->search, fn($q) => $q->where('unit_number', 'like', "%{$this->search}%"))
        ->get();
});

$properties = computed(function () {
    return Property::all();
});

?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-8">
        <div>
            <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">Units</h1>
            <p class="font-inter text-sm mt-1" style="color:#9BABB3;">
                All units across your properties.
            </p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <input wire:model.live="search"
                type="text" placeholder="Search unit..."
                class="font-inter text-sm px-4 py-2 rounded-md focus:outline-none"
                style="background-color:#FFFFFF; color:#283439; width:160px;" />
            <select wire:model.live="property_id"
                class="font-inter text-sm px-4 py-2 rounded-md focus:outline-none"
                style="background-color:#FFFFFF; color:#283439;">
                <option value="">All Properties</option>
                @foreach($this->properties as $property)
                    <option value="{{ $property->id }}">{{ $property->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="status"
                class="font-inter text-sm px-4 py-2 rounded-md focus:outline-none"
                style="background-color:#FFFFFF; color:#283439;">
                <option value="">All Status</option>
                <option value="vacant">Vacant</option>
                <option value="occupied">Occupied</option>
                <option value="maintenance">Maintenance</option>
            </select>
        </div>
    </div>

    {{-- Units List --}}
    <div class="rounded-xl overflow-hidden" style="background-color:#FFFFFF;">
        @forelse($this->units as $unit)
        @php $activeLease = $unit->leases->first(); @endphp
        <div class="flex items-center justify-between px-6 py-4 transition-colors"
            onmouseenter="this.style.backgroundColor='#EFF4F7'"
            onmouseleave="this.style.backgroundColor='transparent'">

            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center
                    font-manrope text-sm font-bold"
                    style="background-color:#E7EFF3; color:#585E6C;">
                    {{ $unit->unit_number }}
                </div>
                <div>
                    <p class="font-inter text-sm font-semibold" style="color:#283439;">
                        {{ $unit->property->name }}
                    </p>
                    <p class="font-inter text-xs mt-0.5" style="color:#9BABB3;">
                        {{ $unit->bedrooms }} bed · {{ $unit->bathrooms }} bath
                    </p>
                </div>
            </div>

            <div class="hidden sm:flex items-center gap-10">
                <div class="text-center">
                    <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Base Rent</p>
                    <p class="font-manrope text-sm font-bold mt-1" style="color:#283439;">
                        KES {{ number_format($unit->base_rent, 0) }}
                    </p>
                </div>
                @if($activeLease)
                <div class="text-center">
                    <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Tenant</p>
                    <p class="font-inter text-sm mt-1" style="color:#283439;">
                        {{ $activeLease->tenant->full_name }}
                    </p>
                </div>
                @endif
                <span class="font-inter text-xs font-medium px-2.5 py-1 rounded-full"
                    style="background-color: {{ $unit->status === 'occupied' ? '#E7EFF3' : ($unit->status === 'vacant' ? '#FDECEA' : '#FEF3C7') }};
                           color: {{ $unit->status === 'occupied' ? '#585E6C' : ($unit->status === 'vacant' ? '#9F403D' : '#92400E') }};">
                    {{ ucfirst($unit->status) }}
                </span>
            </div>

        </div>
        @empty
        <div class="px-6 py-12 text-center">
            <p class="font-inter text-sm" style="color:#9BABB3;">No units found.</p>
        </div>
        @endforelse
    </div>

</div>