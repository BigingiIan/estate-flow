<?php

use App\Models\Transaction;
use App\Models\Property;
use App\Models\Lease;
use function Livewire\Volt\{state, computed, uses};

uses([\Livewire\WithPagination::class]);

state(['search' => '', 'type' => '', 'method' => '']);

$transactions = computed(function () {
    $propertyIds = Property::where('user_id', auth()->id())->pluck('id');
    $unitIds     = \App\Models\Unit::whereIn('property_id', $propertyIds)->pluck('id');
    $leaseIds    = Lease::whereIn('unit_id', $unitIds)->pluck('id');

    return Transaction::whereIn('lease_id', $leaseIds)
        ->with(['lease.tenant', 'lease.unit.property'])
        ->when($this->type,   fn($q) => $q->where('type', $this->type))
        ->when($this->method, fn($q) => $q->where('payment_method', $this->method))
        ->when($this->search, fn($q) => $q->whereHas('lease.tenant',
            fn($q) => $q->where('full_name', 'like', "%{$this->search}%")))
        ->latest('paid_at')
        ->paginate(15);
});

$summary = computed(function () {
    $propertyIds = Property::where('user_id', auth()->id())->pluck('id');
    $unitIds     = \App\Models\Unit::whereIn('property_id', $propertyIds)->pluck('id');
    $leaseIds    = Lease::whereIn('unit_id', $unitIds)->pluck('id');

    return [
        'total_this_month' => Transaction::whereIn('lease_id', $leaseIds)
            ->where('type', 'rent')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount'),
        'total_count' => Transaction::whereIn('lease_id', $leaseIds)->count(),
        'mpesa_count' => Transaction::whereIn('lease_id', $leaseIds)
            ->where('payment_method', 'mpesa')->count(),
    ];
});

?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        {{-- Page Header --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.08em;">
                    Financial records
                </p>
                <h1 class="font-manrope text-2xl font-semibold mt-1" style="color:#24313A;">
                    Transactions
                </h1>
                <p class="font-inter text-sm mt-1" style="color:#7B8794;">
                    All payment records across your portfolio.
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('transactions.create') }}" wire:navigate
                    class="inline-flex items-center justify-center gap-2 font-inter text-xs font-semibold
                        text-white px-5 py-2.5 rounded-lg transition-opacity hover:opacity-90"
                    style="background-color:#35424D;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Record Payment
                </a>
                <a href="{{ route('transactions.export') }}"
                    class="inline-flex items-center justify-center gap-2 font-inter text-xs font-medium
                        px-4 py-2.5 rounded-lg transition-opacity hover:opacity-80"
                    style="background-color:#F7FAFC; color:#60717D; box-shadow:0 0 0 1px #E5EBEF;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export CSV
                </a>
            </div>
        </div>

        {{-- KPI Cards --}}
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-lg p-5" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <div class="flex items-center justify-between">
                    <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.06em;">Collected This Month</p>
                    <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background-color:#E9EEF2;">
                        <svg class="w-4 h-4" style="color:#35424D;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="font-manrope text-2xl font-bold mt-3" style="color:#1F7A4D;">@money($this->summary['total_this_month'])</p>
                <p class="font-inter text-xs mt-2" style="color:#7B8794;">Rent payments received in {{ now()->format('F Y') }}</p>
            </div>

            <div class="rounded-lg p-5" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <div class="flex items-center justify-between">
                    <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.06em;">Total Transactions</p>
                    <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background-color:#E9EEF2;">
                        <svg class="w-4 h-4" style="color:#35424D;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
                <p class="font-manrope text-2xl font-bold mt-3" style="color:#24313A;">{{ number_format($this->summary['total_count']) }}</p>
                <p class="font-inter text-xs mt-2" style="color:#7B8794;">All-time records across portfolio</p>
            </div>

            <div class="rounded-lg p-5" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <div class="flex items-center justify-between">
                    <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.06em;">M-Pesa Payments</p>
                    <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background-color:#E9EEF2;">
                        <svg class="w-4 h-4" style="color:#35424D;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 18h.01M8 21h8a2 2 0 002-2v-1a2 2 0 00-2-2H8a2 2 0 00-2 2v1a2 2 0 002 2zM12 3v9m0 0l-3-3m3 3l3-3"/>
                        </svg>
                    </div>
                </div>
                <p class="font-manrope text-2xl font-bold mt-3" style="color:#24313A;">{{ number_format($this->summary['mpesa_count']) }}</p>
                @php $mpesaPct = $this->summary['total_count'] > 0 ? round(($this->summary['mpesa_count'] / $this->summary['total_count']) * 100) : 0; @endphp
                <div class="mt-2 h-1.5 rounded-full overflow-hidden" style="background:#E9EEF2;">
                    <div class="h-full rounded-full" style="width:{{ $mpesaPct }}%; background:#35424D;"></div>
                </div>
                <p class="font-inter text-xs mt-1.5" style="color:#7B8794;">{{ $mpesaPct }}% of all transactions</p>
            </div>
        </section>

        {{-- Filters + Table --}}
        <section class="rounded-lg overflow-hidden" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
            {{-- Filter bar --}}
            <div class="px-6 py-4 flex flex-col sm:flex-row items-start sm:items-center gap-3 flex-wrap" style="border-bottom:1px solid #E5EBEF;">
                <div class="relative flex-1 min-w-[180px]">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color:#7B8794;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input wire:model.live="search" type="text" placeholder="Search tenant..."
                        class="font-inter text-sm pl-10 pr-4 py-2 rounded-lg border-0 focus:outline-none focus:ring-2 w-full"
                        style="background-color:#F7FAFC; color:#24313A; box-shadow:0 0 0 1px #E5EBEF;" />
                </div>
                <select wire:model.live="type"
                    class="font-inter text-sm px-4 py-2 rounded-lg border-0 focus:outline-none focus:ring-2"
                    style="background-color:#F7FAFC; color:#24313A; box-shadow:0 0 0 1px #E5EBEF;">
                    <option value="">All Types</option>
                    <option value="rent">Rent</option>
                    <option value="deposit">Deposit</option>
                    <option value="penalty">Penalty</option>
                    <option value="refund">Refund</option>
                </select>
                <select wire:model.live="method"
                    class="font-inter text-sm px-4 py-2 rounded-lg border-0 focus:outline-none focus:ring-2"
                    style="background-color:#F7FAFC; color:#24313A; box-shadow:0 0 0 1px #E5EBEF;">
                    <option value="">All Methods</option>
                    <option value="mpesa">M-Pesa</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="cheque">Cheque</option>
                </select>
                <p class="font-inter text-xs ml-auto" style="color:#7B8794;">
                    {{ $this->transactions->total() }} records
                </p>
            </div>

            {{-- Table Header --}}
            <div class="hidden md:grid grid-cols-12 px-6 py-3 font-inter text-xs font-semibold uppercase" style="color:#7B8794; background:#F7FAFC; letter-spacing:0.06em;">
                <p class="col-span-4">Tenant & Reference</p>
                <p class="col-span-2">Type</p>
                <p class="col-span-2">Amount</p>
                <p class="col-span-2">Date & Method</p>
                <p class="col-span-2 text-right">Receipt</p>
            </div>

            @forelse($this->transactions as $txn)
            @php
                $typeColor = match($txn->type) {
                    'rent'    => ['bg' => '#E9EEF2', 'text' => '#35424D'],
                    'deposit' => ['bg' => '#E9EEF2', 'text' => '#1F7A4D'],
                    'penalty' => ['bg' => '#FDECEA', 'text' => '#9F403D'],
                    'refund'  => ['bg' => '#F7FAFC', 'text' => '#60717D'],
                    default   => ['bg' => '#F7FAFC', 'text' => '#60717D'],
                };
            @endphp
            <div class="px-6 py-4 transition-colors" style="border-top:1px solid #EFF3F6;"
                onmouseenter="this.style.backgroundColor='#F7FAFC'"
                onmouseleave="this.style.backgroundColor='transparent'">

                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 md:items-center">

                    {{-- Tenant & Reference --}}
                    <div class="md:col-span-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-manrope text-sm font-bold shrink-0"
                            style="background-color:#E9EEF2; color:#35424D;">
                            {{ strtoupper(substr($txn->lease->tenant->full_name, 0, 1)) }}{{ strtoupper(substr(strstr($txn->lease->tenant->full_name, ' '), 1, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-inter text-sm font-semibold truncate" style="color:#24313A;">
                                {{ $txn->lease->tenant->full_name }}
                            </p>
                            <p class="font-inter text-xs mt-0.5 truncate" style="color:#7B8794;">
                                Unit {{ $txn->lease->unit->unit_number }} · {{ $txn->reference_code }}
                            </p>
                        </div>
                    </div>

                    {{-- Type --}}
                    <div class="md:col-span-2">
                        <p class="font-inter text-xs md:hidden mb-0.5" style="color:#7B8794;">Type</p>
                        <span class="font-inter text-xs font-semibold px-2.5 py-1 rounded-full"
                            style="background:{{ $typeColor['bg'] }}; color:{{ $typeColor['text'] }};">
                            {{ ucfirst($txn->type) }}
                        </span>
                    </div>

                    {{-- Amount --}}
                    <div class="md:col-span-2">
                        <p class="font-inter text-xs md:hidden mb-0.5" style="color:#7B8794;">Amount</p>
                        <p class="font-manrope text-sm font-bold" style="color:#24313A;">@money($txn->amount)</p>
                    </div>

                    {{-- Date & Method --}}
                    <div class="md:col-span-2">
                        <p class="font-inter text-xs md:hidden mb-0.5" style="color:#7B8794;">Date</p>
                        <p class="font-inter text-sm" style="color:#24313A;">
                            @if($txn->paid_at) @appdate($txn->paid_at) @else — @endif
                        </p>
                        <p class="font-inter text-xs mt-0.5" style="color:#7B8794;">
                            {{ $txn->payment_method ? ucfirst(str_replace('_', ' ', $txn->payment_method)) : '—' }}
                        </p>
                    </div>

                    {{-- Receipt --}}
                    <div class="md:col-span-2 flex items-center md:justify-end">
                        <a href="{{ route('transactions.receipt', $txn) }}" wire:navigate
                            class="inline-flex items-center gap-1.5 font-inter text-xs font-medium px-3 py-2 rounded-lg transition-opacity hover:opacity-80"
                            style="background-color:#F7FAFC; color:#60717D; box-shadow:0 0 0 1px #E5EBEF;">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Receipt
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
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <p class="font-manrope text-base font-semibold mb-1" style="color:#24313A;">No transactions found</p>
                <p class="font-inter text-sm mb-4" style="color:#7B8794;">
                    Try adjusting your filters or record a new payment.
                </p>
                <a href="{{ route('transactions.create') }}" wire:navigate
                    class="inline-flex items-center gap-2 font-inter text-xs font-semibold text-white
                        px-5 py-2.5 rounded-lg transition-opacity hover:opacity-90"
                    style="background-color:#35424D;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Record Payment
                </a>
            </div>
            @endforelse

            {{-- Pagination --}}
            @if($this->transactions->hasPages())
            <div class="px-6 py-4" style="border-top:1px solid #E5EBEF;">
                {{ $this->transactions->links() }}
            </div>
            @endif
        </section>

    </div>
</div>
