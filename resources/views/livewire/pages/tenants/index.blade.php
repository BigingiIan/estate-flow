<?php

use App\Models\Tenant;
use App\Models\Lease;
use function Livewire\Volt\{state, computed};

state(['search' => '']);

$tenants = computed(function () {
    return Tenant::with(['leases' => fn($q) => $q->where('status', 'active')->with('unit')])
        ->when($this->search, fn($q) => $q
            ->where('full_name', 'like', "%{$this->search}%")
            ->orWhere('phone', 'like', "%{$this->search}%"))
        ->latest()
        ->get();
});

$summary = computed(function () {
    $total        = Tenant::count();
    $activeLeases = Lease::where('status', 'active')->count();
    $renewalsDue  = Lease::where('status', 'active')
        ->whereNotNull('end_date')
        ->whereBetween('end_date', [now(), now()->addDays(30)])
        ->count();
    return compact('total', 'activeLeases', 'renewalsDue');
});

?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-8">
        <div>
            <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">
                Tenants Directory
            </h1>
            <p class="font-inter text-sm mt-1" style="color:#9BABB3;">
                Manage and track residency status across your portfolio.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <input wire:model.live="search"
                type="text"
                placeholder="Search tenants..."
                class="font-inter text-sm px-4 py-2 rounded-md focus:outline-none"
                style="background-color:#FFFFFF; color:#283439; width:200px;" />
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
        <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
            <p class="font-inter text-xs font-medium uppercase tracking-widest" style="color:#9BABB3;">Total Tenants</p>
            <p class="font-manrope mt-3 text-4xl font-bold" style="color:#283439;">{{ $this->summary['total'] }}</p>
        </div>
        <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
            <p class="font-inter text-xs font-medium uppercase tracking-widest" style="color:#9BABB3;">Active Leases</p>
            <p class="font-manrope mt-3 text-4xl font-bold" style="color:#283439;">{{ $this->summary['activeLeases'] }}</p>
        </div>
        <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
            <p class="font-inter text-xs font-medium uppercase tracking-widest" style="color:#9BABB3;">Renewals Due (30d)</p>
            <p class="font-manrope mt-3 text-4xl font-bold" style="color:#9F403D;">{{ $this->summary['renewalsDue'] }}</p>
        </div>
    </div>

    {{-- Tenant List --}}
    <div class="rounded-xl overflow-hidden" style="background-color:#FFFFFF;">
        @forelse($this->tenants as $tenant)
        @php $activeLease = $tenant->leases->first(); @endphp
        <div class="flex items-center justify-between px-6 py-4 transition-colors"
            onmouseenter="this.style.backgroundColor='#EFF4F7'"
            onmouseleave="this.style.backgroundColor='transparent'">

            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center
                    font-manrope text-sm font-bold"
                    style="background-color:#E7EFF3; color:#585E6C;">
                    {{ strtoupper(substr($tenant->full_name, 0, 1)) }}{{ strtoupper(substr(strstr($tenant->full_name, ' '), 1, 1)) }}
                </div>
                <div>
                    <p class="font-inter text-sm font-semibold" style="color:#283439;">
                        {{ $tenant->full_name }}
                    </p>
                    @if($activeLease)
                    <p class="font-inter text-xs mt-0.5 flex items-center gap-1" style="color:#9BABB3;">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        UNIT {{ $activeLease->unit->unit_number }}
                    </p>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="text-right">
                    <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Rent Status</p>
                    @if($activeLease)
                    @php
                        $paid = $activeLease->transactions()
                            ->where('type', 'rent')
                            ->whereMonth('paid_at', now()->month)
                            ->whereYear('paid_at', now()->year)
                            ->exists();
                    @endphp
                    <span class="font-inter text-xs font-medium px-2.5 py-1 rounded-full"
                        style="background-color: {{ $paid ? '#E7EFF3' : '#FDECEA' }};
                               color: {{ $paid ? '#585E6C' : '#9F403D' }};">
                        {{ $paid ? 'Paid' : 'Pending' }}
                    </span>
                    @else
                    <span class="font-inter text-xs" style="color:#9BABB3;">No lease</span>
                    @endif
                </div>
            </div>

        </div>
        @empty
        <div class="px-6 py-12 text-center">
            <p class="font-inter text-sm" style="color:#9BABB3;">No tenants found.</p>
        </div>
        @endforelse
    </div>

</div>