<?php

use App\Models\Tenant;
use App\Services\TenantService;
use function Livewire\Volt\{state, mount, computed};

state(['tenant' => null]);

mount(function (Tenant $tenant) {
    $this->tenant = $tenant->load([
        'leases.unit.property',
        'leases.transactions',
    ]);
});

$leaseHistory = computed(function () {
    return $this->tenant->leases->sortByDesc('start_date');
});

?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-8">
        <div>
            <a href="{{ route('tenants.index') }}" wire:navigate
                class="font-inter text-xs uppercase tracking-widest flex items-center gap-1 mb-3
                    transition-opacity hover:opacity-70" style="color:#9BABB3;">
                ← Back to Tenants
            </a>
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full flex items-center justify-center
                    font-manrope text-xl font-bold"
                    style="background-color:#E7EFF3; color:#585E6C;">
                    {{ strtoupper(substr($tenant->full_name, 0, 1)) }}{{ strtoupper(substr(strstr($tenant->full_name, ' '), 1, 1)) }}
                </div>
                <div>
                    <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">
                        {{ $tenant->full_name }}
                    </h1>
                    <p class="font-inter text-sm mt-0.5" style="color:#9BABB3;">
                        {{ $tenant->phone }}
                        @if($tenant->email) · {{ $tenant->email }} @endif
                    </p>
                </div>
            </div>
        </div>
        <a href="{{ route('tenants.edit', $tenant) }}" wire:navigate
            class="font-inter text-xs font-semibold text-white px-4 py-2.5 rounded-md
                transition-opacity hover:opacity-90"
            style="background: linear-gradient(135deg, #585E6C, #4C5260);">
            Edit Tenant
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Tenant Info --}}
        <div class="space-y-5">
            <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
                <p class="font-manrope text-sm font-semibold mb-4" style="color:#283439;">
                    Personal Information
                </p>
                <div class="space-y-3">
                    <div>
                        <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">ID Type</p>
                        <p class="font-inter text-sm mt-0.5" style="color:#283439;">
                            {{ ucfirst(str_replace('_', ' ', $tenant->id_type)) }}
                        </p>
                    </div>
                    <div>
                        <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">ID Number</p>
                        <p class="font-inter text-sm mt-0.5" style="color:#283439;">{{ $tenant->id_number }}</p>
                    </div>
                    @if($tenant->emergency_contact)
                    <div>
                        <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Emergency Contact</p>
                        <p class="font-inter text-sm mt-0.5" style="color:#283439;">{{ $tenant->emergency_contact }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Tenant Since</p>
                        <p class="font-inter text-sm mt-0.5" style="color:#283439;">
                            @appdate($tenant->created_at)
                        </p>
                    </div>
                </div>
            </div>

            {{-- Current Lease --}}
            @php $activeLease = $tenant->leases->where('status', 'active')->first(); @endphp
            @if($activeLease)
            <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
                <p class="font-manrope text-sm font-semibold mb-4" style="color:#283439;">
                    Current Lease
                </p>
                <div class="space-y-3">
                    <div>
                        <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Property</p>
                        <p class="font-inter text-sm mt-0.5" style="color:#283439;">
                            {{ $activeLease->unit->property->name }}
                        </p>
                    </div>
                    <div>
                        <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Unit</p>
                        <p class="font-inter text-sm mt-0.5" style="color:#283439;">
                            Unit {{ $activeLease->unit->unit_number }}
                        </p>
                    </div>
                    <div>
                        <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Monthly Rent</p>
                        <p class="font-manrope text-sm font-bold mt-0.5" style="color:#283439;">
                            @money($activeLease->rent_amount)
                        </p>
                    </div>
                    <div>
                        <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Lease Period</p>
                        <p class="font-inter text-sm mt-0.5" style="color:#283439;">
                            @appdate($activeLease->start_date)
                            —
                            @if($activeLease->end_date)
                                @appdate($activeLease->end_date)
                            @else
                                Open-ended
                            @endif
                        </p>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <a href="{{ route('transactions.create', ['lease_id' => $activeLease->id]) }}" wire:navigate
                            class="font-inter text-xs font-semibold text-white px-3 py-1.5 rounded-md 
                                transition-opacity hover:opacity-90"
                            style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                            + Record Payment
                        </a>
                        <a href="{{ route('leases.renew', $activeLease) }}" wire:navigate
                            class="font-inter text-xs font-medium px-3 py-1.5 rounded-md"
                            style="background-color:#E7EFF3; color:#585E6C;">
                            Renew
                        </a>
                        <form method="POST" action="{{ route('leases.terminate', $activeLease) }}"
                            onsubmit="return confirm('Terminate this lease?')">
                            @csrf
                            <button type="submit"
                                class="font-inter text-xs font-medium px-3 py-1.5 rounded-md"
                                style="background-color:#FDECEA; color:#9F403D;">
                                Terminate
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Right: Lease + Payment History --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Payment History --}}
            @if($activeLease)
            <div class="rounded-xl" style="background-color:#FFFFFF;">
                <div class="px-6 py-4" style="border-bottom: 1px solid #EFF4F7;">
                    <p class="font-manrope text-sm font-semibold" style="color:#283439;">
                        Payment History
                    </p>
                </div>
                @forelse($activeLease->transactions->sortByDesc('paid_at') as $txn)
                <div class="flex items-center justify-between px-6 py-3 transition-colors"
                    onmouseenter="this.style.backgroundColor='#EFF4F7'"
                    onmouseleave="this.style.backgroundColor='transparent'">
                    <div>
                        <p class="font-inter text-sm" style="color:#283439;">
                            {{ ucfirst($txn->type) }}
                        </p>
                        <p class="font-inter text-xs mt-0.5" style="color:#9BABB3;">
                            {{ $txn->reference_code }}
                            @if($txn->payment_method)
                                · {{ ucfirst(str_replace('_', ' ', $txn->payment_method)) }}
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <p class="font-manrope text-sm font-bold" style="color:#283439;">
                                @money($txn->amount)
                            </p>
                            <p class="font-inter text-xs" style="color:#9BABB3;">
                                @if($txn->paid_at)
                                    @appdate($txn->paid_at)
                                @else
                                    â€”
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('transactions.receipt', $txn) }}" wire:navigate
                            class="font-inter text-xs font-medium px-3 py-1.5 rounded-md"
                            style="background-color:#E7EFF3; color:#585E6C;">
                            Receipt
                        </a>
                    </div>
                </div>
                @empty
                <div class="px-6 py-8 text-center">
                    <p class="font-inter text-sm" style="color:#9BABB3;">No payments recorded yet.</p>
                </div>
                @endforelse
            </div>
            @endif

            {{-- Lease History --}}
            <div class="rounded-xl" style="background-color:#FFFFFF;">
                <div class="px-6 py-4" style="border-bottom: 1px solid #EFF4F7;">
                    <p class="font-manrope text-sm font-semibold" style="color:#283439;">Lease History</p>
                </div>
                @forelse($this->leaseHistory as $lease)
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <p class="font-inter text-sm font-medium" style="color:#283439;">
                            {{ $lease->unit->property->name }} — Unit {{ $lease->unit->unit_number }}
                        </p>
                        <p class="font-inter text-xs mt-0.5" style="color:#9BABB3;">
                            @appdate($lease->start_date)
                            —
                            @if($lease->end_date)
                                @appdate($lease->end_date)
                            @else
                                Open-ended
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <p class="font-manrope text-sm font-bold" style="color:#283439;">
                            @money($lease->rent_amount)/mo
                        </p>
                        <span class="font-inter text-xs font-medium px-2.5 py-1 rounded-full"
                            style="background-color: {{ $lease->status === 'active' ? '#E7EFF3' : '#EFF4F7' }};
                                   color: {{ $lease->status === 'active' ? '#585E6C' : '#9BABB3' }};">
                            {{ ucfirst($lease->status) }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="px-6 py-8 text-center">
                    <p class="font-inter text-sm" style="color:#9BABB3;">No lease history.</p>
                </div>
                @endforelse
            </div>

        </div>
    </div>
</div>
