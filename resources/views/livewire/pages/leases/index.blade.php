<?php

use App\Models\Lease;
use App\Models\Property;
use function Livewire\Volt\{state, computed};

state(['search' => '', 'status' => 'active']);

$leases = computed(function () {
    $propertyIds = Property::where('user_id', auth()->id())->pluck('id');
    $unitIds = \App\Models\Unit::whereIn('property_id', $propertyIds)->pluck('id');

    return Lease::whereIn('unit_id', $unitIds)
        ->with(['tenant', 'unit.property'])
        ->when($this->status, fn($q) => $q->where('status', $this->status))
        ->when($this->search, fn($q) => $q->whereHas('tenant', fn($q) =>
            $q->where('full_name', 'like', "%{$this->search}%")))
        ->latest()
        ->get();
});

$summary = computed(function () {
    $propertyIds = Property::where('user_id', auth()->id())->pluck('id');
    $unitIds = \App\Models\Unit::whereIn('property_id', $propertyIds)->pluck('id');

    $active    = Lease::whereIn('unit_id', $unitIds)->where('status', 'active')->count();
    $expired   = Lease::whereIn('unit_id', $unitIds)->where('status', 'expired')->count();
    $expiringSoon = Lease::whereIn('unit_id', $unitIds)
        ->where('status', 'active')
        ->whereNotNull('end_date')
        ->whereBetween('end_date', [now(), now()->addDays(60)])
        ->count();

    return compact('active', 'expired', 'expiringSoon');
});

?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        {{-- Page Header --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.08em;">
                    Lease management
                </p>
                <h1 class="font-manrope text-2xl font-semibold mt-1" style="color:#24313A;">
                    Leases
                </h1>
                <p class="font-inter text-sm mt-1" style="color:#7B8794;">
                    All lease agreements across your portfolio.
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                {{-- Search --}}
                <div class="relative">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color:#7B8794;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input wire:model.live="search"
                        type="text" placeholder="Search tenant..."
                        class="font-inter text-sm pl-10 pr-4 py-2.5 rounded-lg border-0 focus:outline-none focus:ring-2 w-full sm:w-52"
                        style="background-color:#FFFFFF; color:#24313A; box-shadow:0 0 0 1px #D9E1E7;" />
                </div>
                {{-- Status filter --}}
                <select wire:model.live="status"
                    class="font-inter text-sm px-4 py-2.5 rounded-lg border-0 focus:outline-none focus:ring-2"
                    style="background-color:#FFFFFF; color:#24313A; box-shadow:0 0 0 1px #D9E1E7;">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="expired">Expired</option>
                    <option value="terminated">Terminated</option>
                </select>
                <a href="{{ route('leases.create') }}" wire:navigate
                    class="inline-flex items-center justify-center gap-2 font-inter text-xs font-semibold
                        text-white px-5 py-2.5 rounded-lg transition-opacity hover:opacity-90"
                    style="background-color:#35424D;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Lease
                </a>
                <a href="{{ route('leases.batch') }}" wire:navigate
                    class="inline-flex items-center justify-center gap-2 font-inter text-xs font-medium px-4 py-2.5 rounded-lg transition-opacity hover:opacity-80"
                    style="background-color:#F7FAFC; color:#60717D; box-shadow:0 0 0 1px #E5EBEF;">
                    Batch
                </a>
            </div>
        </div>

        {{-- KPI Cards --}}
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-lg p-5" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <div class="flex items-center justify-between">
                    <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.06em;">Active Leases</p>
                    <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background-color:#E9EEF2;">
                        <svg class="w-4 h-4" style="color:#35424D;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
                <p class="font-manrope text-2xl font-bold mt-3" style="color:#24313A;">{{ $this->summary['active'] }}</p>
                <p class="font-inter text-xs mt-2" style="color:#7B8794;">Currently running agreements</p>
            </div>

            <div class="rounded-lg p-5" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <div class="flex items-center justify-between">
                    <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.06em;">Expired / Ended</p>
                    <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background-color:#F7FAFC;">
                        <svg class="w-4 h-4" style="color:#60717D;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                </div>
                <p class="font-manrope text-2xl font-bold mt-3" style="color:#60717D;">{{ $this->summary['expired'] }}</p>
                <p class="font-inter text-xs mt-2" style="color:#7B8794;">Expired or terminated leases</p>
            </div>

            <div class="rounded-lg p-5" style="background:{{ $this->summary['expiringSoon'] > 0 ? '#FBF7F2' : '#FFFFFF' }}; box-shadow:0 0 0 1px #E5EBEF;">
                <div class="flex items-center justify-between">
                    <p class="font-inter text-xs font-semibold uppercase" style="color:{{ $this->summary['expiringSoon'] > 0 ? '#8A5A12' : '#7B8794' }}; letter-spacing:0.06em;">Expiring Soon</p>
                    <div class="w-9 h-9 rounded-full flex items-center justify-center"
                        style="background-color:{{ $this->summary['expiringSoon'] > 0 ? '#F5E8D8' : '#E9EEF2' }};">
                        <svg class="w-4 h-4" style="color:{{ $this->summary['expiringSoon'] > 0 ? '#8A5A12' : '#35424D' }};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="font-manrope text-2xl font-bold mt-3"
                    style="color:{{ $this->summary['expiringSoon'] > 0 ? '#8A5A12' : '#24313A' }};">
                    {{ $this->summary['expiringSoon'] }}
                </p>
                <p class="font-inter text-xs mt-2" style="color:#7B8794;">Leases ending within 60 days</p>
            </div>
        </section>

        {{-- Leases Table --}}
        <section class="rounded-lg overflow-hidden" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
            <div class="px-6 py-5 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between" style="border-bottom:1px solid #E5EBEF;">
                <div>
                    <p class="font-manrope text-base font-semibold" style="color:#24313A;">Lease Agreements</p>
                    <p class="font-inter text-xs mt-1" style="color:#7B8794;">
                        Showing {{ $this->leases->count() }} {{ $status ? $status : 'all' }} leases.
                    </p>
                </div>
            </div>

            {{-- Table Header --}}
            <div class="hidden md:grid grid-cols-12 px-6 py-3 font-inter text-xs font-semibold uppercase" style="color:#7B8794; background:#F7FAFC; letter-spacing:0.06em;">
                <p class="col-span-4">Tenant & Property</p>
                <p class="col-span-2">Rent</p>
                <p class="col-span-2">End Date</p>
                <p class="col-span-2">Status</p>
                <p class="col-span-2 text-right">Actions</p>
            </div>

            @forelse($this->leases as $lease)
            @php
                $daysLeft = $lease->end_date ? (int) now()->diffInDays($lease->end_date, false) : null;
                $isExpiringSoon = $daysLeft !== null && $daysLeft <= 60 && $daysLeft >= 0 && $lease->status === 'active';
            @endphp
            <div class="px-6 py-4 transition-colors" style="border-top:1px solid #EFF3F6;"
                onmouseenter="this.style.backgroundColor='#F7FAFC'"
                onmouseleave="this.style.backgroundColor='transparent'">

                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 md:items-center">

                    {{-- Tenant & Property --}}
                    <div class="md:col-span-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-manrope text-sm font-bold shrink-0"
                            style="background-color:#E9EEF2; color:#35424D;">
                            {{ strtoupper(substr($lease->tenant->full_name, 0, 1)) }}{{ strtoupper(substr(strstr($lease->tenant->full_name, ' '), 1, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-inter text-sm font-semibold truncate" style="color:#24313A;">{{ $lease->tenant->full_name }}</p>
                            <p class="font-inter text-xs mt-0.5 truncate" style="color:#7B8794;">
                                {{ $lease->unit->property->name }} · Unit {{ $lease->unit->unit_number }}
                            </p>
                        </div>
                    </div>

                    {{-- Rent --}}
                    <div class="md:col-span-2">
                        <p class="font-inter text-xs md:hidden mb-0.5" style="color:#7B8794;">Rent</p>
                        <p class="font-manrope text-sm font-bold" style="color:#24313A;">@money($lease->rent_amount)</p>
                        <p class="font-inter text-xs mt-0.5" style="color:#7B8794;">per month</p>
                    </div>

                    {{-- End Date --}}
                    <div class="md:col-span-2">
                        <p class="font-inter text-xs md:hidden mb-0.5" style="color:#7B8794;">End Date</p>
                        @if($lease->end_date)
                            <p class="font-inter text-sm" style="color:#24313A;">@appdate($lease->end_date)</p>
                            @if($isExpiringSoon)
                                <p class="font-inter text-xs mt-0.5" style="color:#8A5A12;">{{ $daysLeft }}d remaining</p>
                            @endif
                        @else
                            <p class="font-inter text-sm" style="color:#7B8794;">Open-ended</p>
                        @endif
                    </div>

                    {{-- Status Badge --}}
                    <div class="md:col-span-2">
                        <p class="font-inter text-xs md:hidden mb-0.5" style="color:#7B8794;">Status</p>
                        @php
                            $statusColor = match($lease->status) {
                                'active'     => ['bg' => '#E9EEF2', 'text' => '#35424D'],
                                'expired'    => ['bg' => '#F7FAFC', 'text' => '#60717D'],
                                'terminated' => ['bg' => '#FDECEA', 'text' => '#9F403D'],
                                default      => ['bg' => '#F7FAFC', 'text' => '#60717D'],
                            };
                        @endphp
                        <span class="font-inter text-xs font-semibold px-2.5 py-1 rounded-full"
                            style="background:{{ $statusColor['bg'] }}; color:{{ $statusColor['text'] }};">
                            {{ ucfirst($lease->status) }}
                        </span>
                    </div>

                    {{-- Actions --}}
                    <div class="md:col-span-2 flex items-center gap-2 md:justify-end">
                        @if($lease->status === 'active')
                            <a href="{{ route('leases.renew', $lease) }}" wire:navigate
                                class="font-inter text-xs font-medium px-3 py-2 rounded-lg transition-opacity hover:opacity-80"
                                style="background-color:#F7FAFC; color:#60717D; box-shadow:0 0 0 1px #E5EBEF;">
                                Renew
                            </a>
                            <form method="POST" action="{{ route('leases.terminate', $lease) }}"
                                onsubmit="return confirm('Terminate this lease? The unit will be marked as vacant.')">
                                @csrf
                                <button type="submit"
                                    class="font-inter text-xs font-semibold px-3 py-2 rounded-lg transition-opacity hover:opacity-80"
                                    style="background-color:#FDECEA; color:#9F403D;">
                                    Terminate
                                </button>
                            </form>
                        @else
                            <span class="font-inter text-xs" style="color:#7B8794;">—</span>
                        @endif
                    </div>

                </div>
            </div>
            @empty
            <div class="px-6 py-16 text-center">
                <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4"
                    style="background-color:#E9EEF2;">
                    <svg class="w-7 h-7" style="color:#60717D;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <p class="font-manrope text-base font-semibold mb-1" style="color:#24313A;">No leases found</p>
                <p class="font-inter text-sm mb-4" style="color:#7B8794;">
                    Create your first lease agreement to get started.
                </p>
                <a href="{{ route('leases.create') }}" wire:navigate
                    class="inline-flex items-center gap-2 font-inter text-xs font-semibold text-white
                        px-5 py-2.5 rounded-lg transition-opacity hover:opacity-90"
                    style="background-color:#35424D;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create Lease
                </a>
            </div>
            @endforelse
        </section>

    </div>
</div>
