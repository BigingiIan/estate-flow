<?php

use App\Models\Lease;
use App\Models\Property;
use App\Services\TransactionService;
use function Livewire\Volt\{state, rules, computed};

state([
    'lease_id'       => '',
    'type'           => 'rent',
    'amount'         => '',
    'payment_method' => 'mpesa',
    'paid_at'        => '',
    'notes'          => '',
]);

rules([
    'lease_id'       => ['required', 'exists:leases,id'],
    'type'           => ['required', 'in:rent,deposit,penalty,refund'],
    'amount'         => ['required', 'numeric', 'min:1'],
    'payment_method' => ['required', 'string'],
    'paid_at'        => ['required', 'date'],
    'notes'          => ['nullable', 'string'],
]);

$leases = computed(function () {
    $propertyIds = Property::pluck('id');
    $unitIds = \App\Models\Unit::whereIn('property_id', $propertyIds)->pluck('id');
    return Lease::whereIn('unit_id', $unitIds)
        ->where('status', 'active')
        ->with(['tenant', 'unit'])
        ->get();
});

$updatedLeaseId = function () {
    if ($this->lease_id) {
        $lease = Lease::find($this->lease_id);
        if ($lease) $this->amount = $lease->rent_amount;
    }
};

$save = function (TransactionService $transactionService) {
    $validated = $this->validate();
    $lease = Lease::findOrFail($validated['lease_id']);

    try {
        $transactionService->recordPayment($lease, $validated);
        session()->flash('success', 'Payment recorded successfully.');
        $this->redirect(route('transactions.index'), navigate: true);
    } catch (\Exception $e) {
        $this->addError('lease_id', $e->getMessage());
    }
};

?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <a href="{{ route('transactions.index') }}" wire:navigate
            class="font-inter text-xs uppercase tracking-widest flex items-center gap-1 mb-4
                transition-opacity hover:opacity-70"
            style="color:#9BABB3;">
            ← Back to Transactions
        </a>
        <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">
            Record Payment
        </h1>
        <p class="font-inter text-sm mt-1" style="color:#9BABB3;">
            Record a payment against an active lease.
        </p>
    </div>

    <div class="rounded-xl p-8" style="background-color:#FFFFFF;">
        <form wire:submit="save" class="space-y-6">

            {{-- Lease --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Lease / Tenant</label>
                <select wire:model.live="lease_id"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;">
                    <option value="">Select a lease...</option>
                    @foreach($this->leases as $lease)
                        <option value="{{ $lease->id }}">
                            {{ $lease->tenant->full_name }} — Unit {{ $lease->unit->unit_number }}
                            (KES {{ number_format($lease->rent_amount, 0) }}/mo)
                        </option>
                    @endforeach
                </select>
                @error('lease_id')
                    <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Type + Method --}}
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                        style="color:#9BABB3;">Payment Type</label>
                    <select wire:model="type"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                            focus:outline-none focus:ring-0"
                        style="border-color:#E7EFF3; color:#283439;">
                        <option value="rent">Rent</option>
                        <option value="deposit">Deposit</option>
                        <option value="penalty">Penalty</option>
                        <option value="refund">Refund</option>
                    </select>
                </div>
                <div>
                    <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                        style="color:#9BABB3;">Payment Method</label>
                    <select wire:model="payment_method"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                            focus:outline-none focus:ring-0"
                        style="border-color:#E7EFF3; color:#283439;">
                        <option value="mpesa">M-Pesa</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
            </div>

            {{-- Amount + Date --}}
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                        style="color:#9BABB3;">Amount (KES)</label>
                    <input wire:model="amount" type="number"
                        placeholder="Auto-filled from lease"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                            focus:outline-none focus:ring-0"
                        style="border-color:#E7EFF3; color:#283439;" />
                    @error('amount')
                        <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                        style="color:#9BABB3;">Payment Date</label>
                    <input wire:model="paid_at" type="date"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                            focus:outline-none focus:ring-0"
                        style="border-color:#E7EFF3; color:#283439;" />
                    @error('paid_at')
                        <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Notes <span style="color:#C5D0D5;">(optional)</span></label>
                <textarea wire:model="notes" rows="2"
                    placeholder="e.g. M-Pesa ref XXXXXXXXXXX"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0 resize-none"
                    style="border-color:#E7EFF3; color:#283439;"></textarea>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between pt-4">
                <a href="{{ route('transactions.index') }}" wire:navigate
                    class="font-inter text-sm" style="color:#9BABB3;">Cancel</a>
                <button type="submit"
                    class="font-inter text-xs font-semibold text-white px-6 py-2.5
                        rounded-md transition-opacity hover:opacity-90"
                    style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                    Record Payment
                </button>
            </div>

        </form>
    </div>

</div>