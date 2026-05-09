<?php

use App\Models\Transaction;
use function Livewire\Volt\{state, mount};

state(['transaction' => null]);

mount(function (Transaction $transaction) {
    $this->transaction = $transaction->load([
        'lease.tenant',
        'lease.unit.property',
    ]);
});

?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Print button (hidden when printing) --}}
    <div class="flex items-center justify-between mb-6 print:hidden">
        <a href="{{ route('transactions.index') }}" wire:navigate
            class="font-inter text-xs uppercase tracking-widest flex items-center gap-1
                transition-opacity hover:opacity-70" style="color:#9BABB3;">
            ← Back to Transactions
        </a>
        <button onclick="window.print()"
            class="font-inter text-xs font-semibold text-white px-4 py-2.5
                rounded-md transition-opacity hover:opacity-90"
            style="background: linear-gradient(135deg, #585E6C, #4C5260);">
            Print Receipt
        </button>
    </div>

    {{-- Receipt Card --}}
    <div class="rounded-xl p-8 print:shadow-none" style="background-color:#FFFFFF;">

        {{-- Header --}}
        <div class="flex items-start justify-between mb-8">
            <div>
                <p class="font-manrope text-2xl font-bold" style="color:#283439;">EstateFlow</p>
                <p class="font-inter text-xs mt-1" style="color:#9BABB3;">Payment Receipt</p>
            </div>
            <div class="text-right">
                <span class="font-inter text-xs font-medium px-3 py-1.5 rounded-full"
                    style="background-color:#E7EFF3; color:#585E6C;">
                    {{ strtoupper($transaction->type) }}
                </span>
                <p class="font-inter text-xs mt-2" style="color:#9BABB3;">
                    {{ $transaction->reference_code }}
                </p>
            </div>
        </div>

        {{-- Divider --}}
        <div style="height:1px; background-color:#EFF4F7;" class="mb-6"></div>

        {{-- Amount --}}
        <div class="text-center mb-8">
            <p class="font-inter text-xs uppercase tracking-widest mb-2" style="color:#9BABB3;">
                Amount Paid
            </p>
            <p class="font-manrope font-bold" style="font-size:3rem; color:#283439; line-height:1;">
                @money($transaction->amount)
            </p>
        </div>

        {{-- Divider --}}
        <div style="height:1px; background-color:#EFF4F7;" class="mb-6"></div>

        {{-- Details Grid --}}
        <div class="grid grid-cols-2 gap-y-4">
            <div>
                <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Tenant</p>
                <p class="font-inter text-sm mt-1" style="color:#283439;">
                    {{ $transaction->lease->tenant->full_name }}
                </p>
            </div>
            <div>
                <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Property</p>
                <p class="font-inter text-sm mt-1" style="color:#283439;">
                    {{ $transaction->lease->unit->property->name }}
                </p>
            </div>
            <div>
                <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Unit</p>
                <p class="font-inter text-sm mt-1" style="color:#283439;">
                    Unit {{ $transaction->lease->unit->unit_number }}
                </p>
            </div>
            <div>
                <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Payment Method</p>
                <p class="font-inter text-sm mt-1" style="color:#283439;">
                    {{ $transaction->payment_method
                        ? ucfirst(str_replace('_', ' ', $transaction->payment_method))
                        : '—' }}
                </p>
            </div>
            <div>
                <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Date</p>
                <p class="font-inter text-sm mt-1" style="color:#283439;">
                    {{ $transaction->paid_at
                        ? \Carbon\Carbon::parse($transaction->paid_at)->format('d M Y, H:i')
                        : '—' }}
                </p>
            </div>
            <div>
                <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Reference</p>
                <p class="font-inter text-sm font-medium mt-1" style="color:#283439;">
                    {{ $transaction->reference_code }}
                </p>
            </div>
            @if($transaction->notes)
            <div class="col-span-2">
                <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Notes</p>
                <p class="font-inter text-sm mt-1" style="color:#283439;">{{ $transaction->notes }}</p>
            </div>
            @endif
        </div>

        {{-- Divider --}}
        <div style="height:1px; background-color:#EFF4F7;" class="my-6"></div>

        {{-- Footer --}}
        <p class="font-inter text-xs text-center" style="color:#9BABB3;">
            This is an official payment receipt generated by EstateFlow.
            Please retain for your records.
        </p>

    </div>
</div>

{{-- Print styles --}}
<style>
    @media print {
        nav, .print\:hidden { display: none !important; }
        body { background: white !important; }
        .rounded-xl { border-radius: 0 !important; }
    }
</style>
