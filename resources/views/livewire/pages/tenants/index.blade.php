<?php

use App\Models\Tenant;
use App\Models\Lease;
use function Livewire\Volt\{state, computed};

state(['search' => '']);

$tenants = computed(function () {
    return Tenant::with([
        'leases' => fn($q) => $q->where('status', 'active')
            ->with(['unit', 'transactions' => fn($q) => $q
                ->where('type', 'rent')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
            ])
    ])
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
            <input wire:model.live="search" type="text" placeholder="Search tenants..."
                class="font-inter text-sm px-4 py-2 rounded-md focus:outline-none"
                style="background-color:#FFFFFF; color:#283439; width:200px;" />
            <a href="{{ route('tenants.create') }}" wire:navigate
                class="inline-flex items-center gap-2 font-inter text-xs font-semibold
                    text-white px-4 py-2.5 rounded-md transition-opacity hover:opacity-90"
                style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                + Add Tenant
            </a>
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
                        $paid = $activeLease->transactions->isNotEmpty();
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

                @if($activeLease && !($activeLease->transactions->isNotEmpty()))
                <form method="POST" action="{{ route('tenants.nudge', $tenant) }}">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-2 font-inter text-xs font-medium
                            px-3 py-2 rounded-md transition-opacity hover:opacity-80"
                        style="background-color:#E7EFF3; color:#585E6C;">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Nudge
                    </button>
                </form>
                @endif
            </div>

        </div>
        @empty
        <div class="px-6 py-12 text-center">
            <p class="font-inter text-sm" style="color:#9BABB3;">No tenants found.</p>
        </div>
        @endforelse
    </div>

</div>