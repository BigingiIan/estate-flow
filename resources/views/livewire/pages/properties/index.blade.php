<?php

use function Livewire\Volt\{state, computed, mount};
use App\Models\Property;

state(['search' => '']);

$properties = computed(function () {
    return Property::withCount('units')
        ->with(['units' => fn($q) => $q->where('status', 'vacant')])
        ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
            ->orWhere('location', 'like', "%{$this->search}%"))
        ->latest()
        ->get();
});

$summary = computed(function () {
    $properties = Property::withCount('units')->with('units')->get();
    $totalUnits    = $properties->sum('units_count');
    $vacantUnits   = $properties->sum(fn($p) => $p->units->where('status', 'vacant')->count());
    $occupiedUnits = $totalUnits - $vacantUnits;
    $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0;

    return compact('totalUnits', 'vacantUnits', 'occupancyRate');
});

?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Page Header --}}
    <div class="flex items-start justify-between mb-8">
        <div>
            <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">
                Properties Portfolio
            </h1>
            <p class="font-inter text-sm mt-1" style="color:#9BABB3;">
                Managing {{ $this->properties->count() }} active locations across the region.
            </p>
        </div>
        <a href="{{ route('properties.create') }}" wire:navigate
            class="inline-flex items-center gap-2 font-inter text-xs font-semibold
                text-white px-4 py-2.5 rounded-md transition-opacity hover:opacity-90"
            style="background: linear-gradient(135deg, #585E6C, #4C5260);">
            + Add Property
        </a>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
        <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
            <p class="font-inter text-xs font-medium uppercase tracking-widest" style="color:#9BABB3;">Total Units</p>
            <p class="font-manrope mt-3 text-4xl font-bold" style="color:#283439;">{{ $this->summary['totalUnits'] }}</p>
        </div>
        <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
            <p class="font-inter text-xs font-medium uppercase tracking-widest" style="color:#9BABB3;">Vacancies</p>
            <p class="font-manrope mt-3 text-4xl font-bold" style="color:#9F403D;">{{ $this->summary['vacantUnits'] }}</p>
        </div>
        <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
            <p class="font-inter text-xs font-medium uppercase tracking-widest" style="color:#9BABB3;">Occupancy</p>
            <p class="font-manrope mt-3 text-4xl font-bold" style="color:#283439;">{{ $this->summary['occupancyRate'] }}%</p>
        </div>
    </div>

    {{-- Search --}}
    <div class="mb-6">
        <input wire:model.live="search"
            type="text"
            placeholder="Search properties..."
            class="font-inter text-sm px-4 py-2.5 rounded-md w-full sm:w-72 focus:outline-none"
            style="background-color:#FFFFFF; color:#283439;" />
    </div>

    {{-- Properties List --}}
    <div class="rounded-xl overflow-hidden" style="background-color:#FFFFFF;">
        @forelse($this->properties as $property)
        <div class="flex items-center justify-between px-6 py-5 transition-colors"
            onmouseenter="this.style.backgroundColor='#EFF4F7'"
            onmouseleave="this.style.backgroundColor='transparent'">

            <div class="flex items-center gap-4">
                {{-- Property icon --}}
                <div class="w-12 h-12 rounded-lg flex items-center justify-center font-manrope font-bold text-lg"
                    style="background-color:#E7EFF3; color:#585E6C;">
                    {{ strtoupper(substr($property->name, 0, 1)) }}
                </div>
                <div>
                    <p class="font-inter text-sm font-semibold" style="color:#283439;">
                        {{ $property->name }}
                    </p>
                    <p class="font-inter text-xs mt-0.5 flex items-center gap-1" style="color:#9BABB3;">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ $property->location }}
                    </p>
                </div>
            </div>

            <div class="hidden sm:flex items-center gap-12">
                <div class="text-center">
                    <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Total Units</p>
                    <p class="font-manrope text-lg font-bold mt-1" style="color:#283439;">
                        {{ $property->units_count }}
                    </p>
                </div>
                <div class="text-center">
                    <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Availability</p>
                    @php $vacant = $property->units->where('status', 'vacant')->count(); @endphp
                    <p class="font-manrope text-lg font-bold mt-1"
                        style="color: {{ $vacant > 0 ? '#9F403D' : '#283439' }};">
                        {{ $vacant }} Vacant
                    </p>
                </div>
                <a href="{{ route('properties.show', $property) }}" wire:navigate
                    class="font-inter text-xs font-semibold text-white px-4 py-2 rounded-md
                        transition-opacity hover:opacity-90"
                    style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                    Manage
                </a>
            </div>
        </div>
        @empty
        <div class="px-6 py-12 text-center">
            <p class="font-inter text-sm" style="color:#9BABB3;">
                No properties found. Add your first property to get started.
            </p>
        </div>
        @endforelse
    </div>

</div>