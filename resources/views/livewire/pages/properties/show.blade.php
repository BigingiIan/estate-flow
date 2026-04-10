<?php

use App\Models\Property;
use App\Models\Unit;
use function Livewire\Volt\{state, mount};

state(['property' => null]);

mount(function (Property $property) {
    $this->property = $property->load(['units.leases.tenant']);
});

?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-8">
        <div>
            <a href="{{ route('properties.index') }}" wire:navigate
                class="font-inter text-xs uppercase tracking-widest flex items-center gap-1 mb-3
                    transition-opacity hover:opacity-70"
                style="color:#9BABB3;">
                ← Back to Properties
            </a>
            <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">
                {{ $property->name }}
            </h1>
            <p class="font-inter text-sm mt-1 flex items-center gap-1" style="color:#9BABB3;">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ $property->location }}
            </p>
        </div>
        <a href="{{ route('units.create', $property) }}" wire:navigate
            class="inline-flex items-center gap-2 font-inter text-xs font-semibold
                text-white px-4 py-2.5 rounded-md transition-opacity hover:opacity-90"
            style="background: linear-gradient(135deg, #585E6C, #4C5260);">
            + Add Unit
        </a>
    </div>

    {{-- Units Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($property->units as $unit)
        @php
            $activeLease = $unit->leases->where('status', 'active')->first();
        @endphp
        <div class="rounded-xl p-6" style="background-color:#FFFFFF;">

            <div class="flex items-center justify-between mb-4">
                <p class="font-manrope text-base font-semibold" style="color:#283439;">
                    Unit {{ $unit->unit_number }}
                </p>
                <span class="font-inter text-xs font-medium uppercase tracking-widest px-2.5 py-1 rounded-full"
                    style="background-color: {{ $unit->status === 'occupied' ? '#E7EFF3' : '#FDECEA' }};
                           color: {{ $unit->status === 'occupied' ? '#585E6C' : '#9F403D' }};">
                    {{ ucfirst($unit->status) }}
                </span>
            </div>

            <div class="space-y-2 mb-4">
                <div class="flex justify-between">
                    <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Base Rent</p>
                    <p class="font-manrope text-sm font-semibold" style="color:#283439;">
                        KES {{ number_format($unit->base_rent, 0) }}
                    </p>
                </div>
                <div class="flex justify-between">
                    <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Bedrooms</p>
                    <p class="font-inter text-sm" style="color:#283439;">{{ $unit->bedrooms }} bed / {{ $unit->bathrooms }} bath</p>
                </div>
                @if($activeLease)
                <div class="flex justify-between">
                    <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Tenant</p>
                    <p class="font-inter text-sm" style="color:#283439;">{{ $activeLease->tenant->full_name }}</p>
                </div>
                <div class="flex justify-between">
                    <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Lease Ends</p>
                    <p class="font-inter text-sm" style="color:#283439;">
                        {{ \Carbon\Carbon::parse($activeLease->end_date)->format('d M Y') }}
                    </p>
                </div>
                @endif
            </div>

            {{-- Edit button inside each unit card at the bottom --}}
            <div class="flex items-center gap-2 mt-4 pt-4" style="border-top: 1px solid #EFF4F7;">
                <a href="{{ route('units.edit', $unit) }}" wire:navigate
                    class="font-inter text-xs font-medium px-3 py-1.5 rounded-md transition-opacity hover:opacity-80"
                    style="background-color:#E7EFF3; color:#585E6C;">
                    Edit Unit
                </a>
            </div>
        </div>
        @endforeach
    </div>

</div>