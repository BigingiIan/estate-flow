<?php

use function Livewire\Volt\{state, computed, uses};
use App\Models\Property;
use Livewire\WithPagination;

uses(WithPagination::class);

state(['search' => '']);

$properties = computed(function () {
    return Property::withCount('units')
        ->with(['units' => fn($q) => $q->where('status', 'vacant')])
        ->when($this->search, fn($q) => $q
            ->where('name', 'like', "%{$this->search}%")
            ->orWhere('location', 'like', "%{$this->search}%"))
        ->latest()
        ->paginate(10);
});

$summary = computed(function () {
    $properties = Property::withCount('units')->with('units')->get();
    $totalUnits    = $properties->sum('units_count');
    $vacantUnits   = $properties->sum(fn($p) => $p->units->where('status', 'vacant')->count());
    $occupiedUnits = $totalUnits - $vacantUnits;
    $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0;

    return compact('totalUnits', 'vacantUnits', 'occupiedUnits', 'occupancyRate');
});

?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        {{-- Page Header --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.08em;">
                    Real estate management
                </p>
                <h1 class="font-manrope text-2xl font-semibold mt-1" style="color:#24313A;">
                    Properties Portfolio
                </h1>
                <p class="font-inter text-sm mt-1" style="color:#7B8794;">
                    Managing {{ $this->properties->total() }} active locations across your portfolio.
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color:#7B8794;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input wire:model.live="search"
                        type="text"
                        placeholder="Search properties..."
                        class="font-inter text-sm pl-10 pr-4 py-2.5 rounded-lg border-0 focus:outline-none focus:ring-2 w-full sm:w-64"
                        style="background-color:#FFFFFF; color:#24313A; box-shadow:0 0 0 1px #D9E1E7;" />
                </div>
                <a href="{{ route('properties.create') }}" wire:navigate
                    class="inline-flex items-center justify-center gap-2 font-inter text-xs font-semibold
                        text-white px-5 py-2.5 rounded-lg transition-opacity hover:opacity-90"
                    style="background-color:#35424D;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Property
                </a>
            </div>
        </div>

        {{-- KPI Cards --}}
        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="rounded-lg p-5" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <div class="flex items-center justify-between">
                    <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.06em;">Properties</p>
                    <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background-color:#E9EEF2;">
                        <svg class="w-4 h-4" style="color:#35424D;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                </div>
                <p class="font-manrope text-2xl font-bold mt-3" style="color:#24313A;">{{ $this->properties->total() }}</p>
                <p class="font-inter text-xs mt-2" style="color:#7B8794;">Active locations in portfolio</p>
            </div>

            <div class="rounded-lg p-5" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <div class="flex items-center justify-between">
                    <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.06em;">Total Units</p>
                    <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background-color:#E9EEF2;">
                        <svg class="w-4 h-4" style="color:#35424D;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                    </div>
                </div>
                <p class="font-manrope text-2xl font-bold mt-3" style="color:#24313A;">{{ $this->summary['totalUnits'] }}</p>
                <p class="font-inter text-xs mt-2" style="color:#7B8794;">Across all properties</p>
            </div>

            <div class="rounded-lg p-5" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <div class="flex items-center justify-between">
                    <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.06em;">Vacancies</p>
                    <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background-color:#FDECEA;">
                        <svg class="w-4 h-4" style="color:#9F403D;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
                <p class="font-manrope text-2xl font-bold mt-3" style="color:#9F403D;">{{ $this->summary['vacantUnits'] }}</p>
                <p class="font-inter text-xs mt-2" style="color:#7B8794;">Units currently unoccupied</p>
            </div>

            <div class="rounded-lg p-5" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <div class="flex items-center justify-between">
                    <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.06em;">Occupancy</p>
                    <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background-color:#E9EEF2;">
                        <svg class="w-4 h-4" style="color:#35424D;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
                <p class="font-manrope text-2xl font-bold mt-3"
                    style="color:{{ $this->summary['occupancyRate'] >= 85 ? '#1F7A4D' : ($this->summary['occupancyRate'] >= 65 ? '#8A5A12' : '#9F403D') }};">
                    {{ $this->summary['occupancyRate'] }}%
                </p>
                <div class="mt-2 h-1.5 rounded-full overflow-hidden" style="background:#E9EEF2;">
                    <div class="h-full rounded-full" style="width:{{ $this->summary['occupancyRate'] }}%; background:#35424D;"></div>
                </div>
                <p class="font-inter text-xs mt-1.5" style="color:#7B8794;">
                    {{ $this->summary['occupiedUnits'] }} of {{ $this->summary['totalUnits'] }} units occupied
                </p>
            </div>
        </section>

        {{-- Properties Table --}}
        <section class="rounded-lg overflow-hidden" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
            <div class="px-6 py-5 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between" style="border-bottom:1px solid #E5EBEF;">
                <div>
                    <p class="font-manrope text-base font-semibold" style="color:#24313A;">All Properties</p>
                    <p class="font-inter text-xs mt-1" style="color:#7B8794;">Click manage to view units, tenants, and leases.</p>
                </div>
                <p class="font-inter text-xs" style="color:#7B8794;">{{ $this->properties->total() }} properties</p>
            </div>

            {{-- Table Header --}}
            <div class="hidden md:grid grid-cols-12 px-6 py-3 font-inter text-xs font-semibold uppercase" style="color:#7B8794; background:#F7FAFC; letter-spacing:0.06em;">
                <p class="col-span-4">Property</p>
                <p class="col-span-2">Total Units</p>
                <p class="col-span-2">Vacancies</p>
                <p class="col-span-2">Occupancy</p>
                <p class="col-span-2 text-right">Actions</p>
            </div>

            @forelse($this->properties as $property)
            @php
                $vacant = $property->units->where('status', 'vacant')->count();
                $occupancy = $property->units_count > 0 ? round((($property->units_count - $vacant) / $property->units_count) * 100) : 0;
            @endphp
            <div class="px-6 py-4 transition-colors" style="border-top:1px solid #EFF3F6;"
                onmouseenter="this.style.backgroundColor='#F7FAFC'"
                onmouseleave="this.style.backgroundColor='transparent'">

                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 md:items-center">
                    {{-- Property info --}}
                    <div class="md:col-span-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center font-manrope font-bold text-sm shrink-0"
                            style="background-color:#E9EEF2; color:#35424D;">
                            {{ strtoupper(substr($property->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-inter text-sm font-semibold truncate" style="color:#24313A;">{{ $property->name }}</p>
                            <p class="font-inter text-xs flex items-center gap-1 mt-0.5" style="color:#7B8794;">
                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="truncate">{{ $property->location }}</span>
                            </p>
                        </div>
                    </div>

                    {{-- Total Units --}}
                    <div class="md:col-span-2">
                        <p class="font-inter text-xs md:hidden" style="color:#7B8794;">Total Units</p>
                        <p class="font-manrope text-sm font-bold" style="color:#24313A;">{{ $property->units_count }}</p>
                    </div>

                    {{-- Vacancies --}}
                    <div class="md:col-span-2">
                        <p class="font-inter text-xs md:hidden" style="color:#7B8794;">Vacancies</p>
                        <span class="font-inter text-xs font-semibold px-2.5 py-1 rounded-full"
                            style="background:{{ $vacant > 0 ? '#FDECEA' : '#E9EEF2' }}; color:{{ $vacant > 0 ? '#9F403D' : '#35424D' }};">
                            {{ $vacant }} vacant
                        </span>
                    </div>

                    {{-- Occupancy --}}
                    <div class="md:col-span-2">
                        <p class="font-inter text-xs md:hidden" style="color:#7B8794;">Occupancy</p>
                        <p class="font-inter text-sm" style="color:#24313A;">{{ $occupancy }}%</p>
                        <div class="h-1 rounded-full overflow-hidden mt-1 max-w-[80px]" style="background:#E9EEF2;">
                            <div class="h-full rounded-full" style="width:{{ $occupancy }}%; background:{{ $occupancy >= 85 ? '#1F7A4D' : ($occupancy >= 65 ? '#8A5A12' : '#9F403D') }};"></div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="md:col-span-2 flex items-center gap-2 md:justify-end">
                        <a href="{{ route('properties.show', $property) }}" wire:navigate
                            class="font-inter text-xs font-semibold text-white px-4 py-2 rounded-lg
                                transition-opacity hover:opacity-90"
                            style="background-color:#35424D;">
                            Manage
                        </a>
                        <a href="{{ route('properties.edit', $property) }}" wire:navigate
                            class="font-inter text-xs font-medium px-3 py-2 rounded-lg transition-opacity hover:opacity-80"
                            style="background-color:#F7FAFC; color:#60717D; box-shadow:0 0 0 1px #E5EBEF;">
                            Edit
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="px-6 py-16 text-center">
                <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4"
                    style="background-color:#E9EEF2;">
                    <svg class="w-7 h-7" style="color:#60717D;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <p class="font-manrope text-base font-semibold mb-1" style="color:#24313A;">No properties yet</p>
                <p class="font-inter text-sm mb-4" style="color:#7B8794;">
                    Add your first property to get started.
                </p>
                <a href="{{ route('properties.create') }}" wire:navigate
                    class="inline-flex items-center gap-2 font-inter text-xs font-semibold text-white
                        px-5 py-2.5 rounded-lg transition-opacity hover:opacity-90"
                    style="background-color:#35424D;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Property
                </a>
            </div>
            @endforelse
        </section>

        {{-- Pagination --}}
        @if($this->properties->hasPages())
        <div class="px-2 py-4">
            {{ $this->properties->links() }}
        </div>
        @endif

    </div>
</div>