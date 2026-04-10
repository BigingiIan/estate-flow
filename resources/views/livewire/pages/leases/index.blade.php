<?php

use App\Models\Lease;
use App\Models\Property;
use function Livewire\Volt\{state, computed};

state(['search' => '', 'status' => 'active']);

$leases = computed(function () {
    $propertyIds = Property::pluck('id');
    $unitIds = \App\Models\Unit::whereIn('property_id', $propertyIds)->pluck('id');

    return Lease::whereIn('unit_id', $unitIds)
        ->with(['tenant', 'unit.property'])
        ->when($this->status, fn($q) => $q->where('status', $this->status))
        ->when($this->search, fn($q) => $q->whereHas('tenant', fn($q) =>
            $q->where('full_name', 'like', "%{$this->search}%")))
        ->latest()
        ->get();
});

?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-8">
        <div>
            <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">Leases</h1>
            <p class="font-inter text-sm mt-1" style="color:#9BABB3;">
                All lease agreements across your portfolio.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <input wire:model.live="search"
                type="text" placeholder="Search tenant..."
                class="font-inter text-sm px-4 py-2 rounded-md focus:outline-none"
                style="background-color:#FFFFFF; color:#283439; width:200px;" />
            <select wire:model.live="status"
                class="font-inter text-sm px-4 py-2 rounded-md focus:outline-none"
                style="background-color:#FFFFFF; color:#283439;">
                <option value="">All</option>
                <option value="active">Active</option>
                <option value="expired">Expired</option>
                <option value="terminated">Terminated</option>
            </select>
            <a href="{{ route('leases.create') }}" wire:navigate
                class="inline-flex items-center gap-2 font-inter text-xs font-semibold
                    text-white px-4 py-2.5 rounded-md transition-opacity hover:opacity-90"
                style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                + New Lease
            </a>
        </div>
    </div>

    {{-- Leases List --}}
    <div class="rounded-xl overflow-hidden" style="background-color:#FFFFFF;">
        @forelse($this->leases as $lease)
        <div class="flex items-center justify-between px-6 py-4 transition-colors"
            onmouseenter="this.style.backgroundColor='#EFF4F7'"
            onmouseleave="this.style.backgroundColor='transparent'">

            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center
                    font-manrope text-sm font-bold"
                    style="background-color:#E7EFF3; color:#585E6C;">
                    {{ strtoupper(substr($lease->tenant->full_name, 0, 1)) }}{{ strtoupper(substr(strstr($lease->tenant->full_name, ' '), 1, 1)) }}
                </div>
                <div>
                    <p class="font-inter text-sm font-semibold" style="color:#283439;">
                        {{ $lease->tenant->full_name }}
                    </p>
                    <p class="font-inter text-xs mt-0.5" style="color:#9BABB3;">
                        {{ $lease->unit->property->name }} — Unit {{ $lease->unit->unit_number }}
                    </p>
                </div>
            </div>

            <div class="hidden sm:flex items-center gap-10">
                <div class="text-center">
                    <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Rent</p>
                    <p class="font-manrope text-sm font-bold mt-1" style="color:#283439;">
                        KES {{ number_format($lease->rent_amount, 0) }}
                    </p>
                </div>
                <div class="text-center">
                    <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">End Date</p>
                    <p class="font-inter text-sm mt-1" style="color:#283439;">
                        {{ $lease->end_date ? \Carbon\Carbon::parse($lease->end_date)->format('d M Y') : 'Open' }}
                    </p>
                </div>
                <span class="font-inter text-xs font-medium px-2.5 py-1 rounded-full"
                    style="background-color: {{ $lease->status === 'active' ? '#E7EFF3' : '#FDECEA' }};
                           color: {{ $lease->status === 'active' ? '#585E6C' : '#9F403D' }};">
                    {{ ucfirst($lease->status) }}
                </span>

                @if($lease->status === 'active')
                <form method="POST" action="{{ route('leases.terminate', $lease) }}"
                    onsubmit="return confirm('Terminate this lease? The unit will be marked as vacant.')">
                    @csrf
                    <button type="submit"
                        class="font-inter text-xs font-medium px-3 py-1.5 rounded-md transition-opacity hover:opacity-80"
                        style="background-color:#FDECEA; color:#9F403D;">
                        Terminate
                    </button>
                </form>
                @endif
            </div>

        </div>
        @empty
        <div class="px-6 py-12 text-center">
            <p class="font-inter text-sm" style="color:#9BABB3;">No leases found.</p>
        </div>
        @endforelse
    </div>

</div>
