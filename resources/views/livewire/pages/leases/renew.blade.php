<?php

use App\Models\Lease;
use function Livewire\Volt\{state, rules, mount};

state(['lease' => null, 'end_date' => '', 'rent_amount' => '', 'notes' => '']);

rules([
    'end_date'    => ['required', 'date', 'after:today'],
    'rent_amount' => ['required', 'numeric', 'min:0'],
    'notes'       => ['nullable', 'string'],
]);

mount(function (Lease $lease) {
    $this->lease       = $lease->load(['tenant', 'unit.property']);
    $this->end_date    = $lease->end_date
        ? \Carbon\Carbon::parse($lease->end_date)->addYear()->format('Y-m-d')
        : now()->addYear()->format('Y-m-d');
    $this->rent_amount = $lease->rent_amount;
});

$renew = function () {
    $validated = $this->validate();

    $this->lease->update([
        'end_date'    => $validated['end_date'],
        'rent_amount' => $validated['rent_amount'],
        'status'      => 'active',
        'notes'       => $validated['notes'] ?? $this->lease->notes,
    ]);

    // Update unit rent if changed
    if ($validated['rent_amount'] != $this->lease->unit->base_rent) {
        $this->lease->unit->update(['base_rent' => $validated['rent_amount']]);
    }

    session()->flash('success', 'Lease renewed successfully.');
    $this->redirect(route('tenants.show', $this->lease->tenant), navigate: true);
};

?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <a href="{{ route('tenants.show', $lease->tenant) }}" wire:navigate
            class="font-inter text-xs uppercase tracking-widest flex items-center gap-1 mb-4
                transition-opacity hover:opacity-70" style="color:#9BABB3;">
            ← Back to {{ $lease->tenant->full_name }}
        </a>
        <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">Renew Lease</h1>
        <p class="font-inter text-sm mt-1" style="color:#9BABB3;">
            {{ $lease->unit->property->name }} — Unit {{ $lease->unit->unit_number }}
        </p>
    </div>

    <div class="rounded-xl p-8" style="background-color:#FFFFFF;">
        <form wire:submit="renew" class="space-y-6">

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">New End Date</label>
                <input wire:model="end_date" type="date"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('end_date') <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Rent Amount (KES)</label>
                <input wire:model="rent_amount" type="number"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('rent_amount') <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p> @enderror
                <p class="font-inter text-xs mt-1" style="color:#9BABB3;">
                    Current rate: @money($lease->rent_amount)/mo
                </p>
            </div>

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Notes <span style="color:#C5D0D5;">(optional)</span></label>
                <textarea wire:model="notes" rows="2"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0 resize-none"
                    style="border-color:#E7EFF3; color:#283439;"></textarea>
            </div>

            <div class="flex items-center justify-between pt-4">
                <a href="{{ route('tenants.show', $lease->tenant) }}" wire:navigate
                    class="font-inter text-sm" style="color:#9BABB3;">Cancel</a>
                <button type="submit"
                    class="font-inter text-xs font-semibold text-white px-6 py-2.5
                        rounded-md transition-opacity hover:opacity-90"
                    style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                    Confirm Renewal
                </button>
            </div>
        </form>
    </div>
</div>
