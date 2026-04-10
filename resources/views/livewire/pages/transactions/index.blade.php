<?php

use App\Models\Transaction;
use App\Models\Property;
use function Livewire\Volt\{state, computed};

state(['search' => '', 'type' => '']);

$transactions = computed(function () {
    $propertyIds = Property::pluck('id');
    $unitIds = \App\Models\Unit::whereIn('property_id', $propertyIds)->pluck('id');
    $leaseIds = \App\Models\Lease::whereIn('unit_id', $unitIds)->pluck('id');

    return Transaction::whereIn('lease_id', $leaseIds)
        ->with(['lease.tenant', 'lease.unit'])
        ->when($this->type, fn($q) => $q->where('type', $this->type))
        ->when($this->search, fn($q) => $q->whereHas('lease.tenant', fn($q) =>
            $q->where('full_name', 'like', "%{$this->search}%")))
        ->latest('paid_at')
        ->get();
});

?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-8">
        <div>
            <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">Transactions</h1>
            <p class="font-inter text-sm mt-1" style="color:#9BABB3;">
                All payment records across your portfolio.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <input wire:model.live="search"
                type="text" placeholder="Search tenant..."
                class="font-inter text-sm px-4 py-2 rounded-md focus:outline-none"
                style="background-color:#FFFFFF; color:#283439; width:200px;" />
            <select wire:model.live="type"
                class="font-inter text-sm px-4 py-2 rounded-md focus:outline-none"
                style="background-color:#FFFFFF; color:#283439;">
                <option value="">All Types</option>
                <option value="rent">Rent</option>
                <option value="deposit">Deposit</option>
                <option value="penalty">Penalty</option>
                <option value="refund">Refund</option>
            </select>
        </div>
        <a href="{{ route('transactions.create') }}" wire:navigate
            class="inline-flex items-center gap-2 font-inter text-xs font-semibold
                text-white px-4 py-2.5 rounded-md transition-opacity hover:opacity-90"
            style="background: linear-gradient(135deg, #585E6C, #4C5260);">
            + Record Payment
        </a>
    </div>

    {{-- Transactions List --}}
    <div class="rounded-xl overflow-hidden" style="background-color:#FFFFFF;">
        @forelse($this->transactions as $txn)
        <div class="flex items-center justify-between px-6 py-4 transition-colors"
            onmouseenter="this.style.backgroundColor='#EFF4F7'"
            onmouseleave="this.style.backgroundColor='transparent'">

            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center
                    font-manrope text-sm font-bold"
                    style="background-color:#E7EFF3; color:#585E6C;">
                    {{ strtoupper(substr($txn->lease->tenant->full_name, 0, 1)) }}{{ strtoupper(substr(strstr($txn->lease->tenant->full_name, ' '), 1, 1)) }}
                </div>
                <div>
                    <p class="font-inter text-sm font-semibold" style="color:#283439;">
                        {{ $txn->lease->tenant->full_name }}
                    </p>
                    <p class="font-inter text-xs mt-0.5" style="color:#9BABB3;">
                        Unit {{ $txn->lease->unit->unit_number }} — {{ $txn->reference_code }}
                    </p>
                </div>
            </div>

            <div class="hidden sm:flex items-center gap-10">
                <div class="text-center">
                    <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Type</p>
                    <span class="font-inter text-xs font-medium px-2.5 py-1 rounded-full mt-1 inline-block"
                        style="background-color:#E7EFF3; color:#585E6C;">
                        {{ ucfirst($txn->type) }}
                    </span>
                </div>
                <div class="text-center">
                    <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Amount</p>
                    <p class="font-manrope text-sm font-bold mt-1" style="color:#283439;">
                        KES {{ number_format($txn->amount, 0) }}
                    </p>
                </div>
                <div class="text-center">
                    <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Date</p>
                    <p class="font-inter text-sm mt-1" style="color:#283439;">
                        {{ $txn->paid_at ? \Carbon\Carbon::parse($txn->paid_at)->format('d M Y') : '—' }}
                    </p>
                </div>
                <div class="text-center">
                    <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Method</p>
                    <p class="font-inter text-sm mt-1" style="color:#283439;">
                        {{ $txn->payment_method ? ucfirst(str_replace('_', ' ', $txn->payment_method)) : '—' }}
                    </p>
                </div>
            </div>

        </div>
        @empty
        <div class="px-6 py-12 text-center">
            <p class="font-inter text-sm" style="color:#9BABB3;">No transactions found.</p>
        </div>
        @endforelse
    </div>

</div>