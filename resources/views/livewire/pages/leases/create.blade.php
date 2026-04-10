<?php

use App\Models\Lease;
use App\Models\Unit;
use App\Models\Tenant;
use App\Models\Property;
use App\Services\LeaseService;
use function Livewire\Volt\{state, rules, computed, mount};

state([
    'property_id'    => '',
    'unit_id'        => '',
    'tenant_id'      => '',
    'start_date'     => '',
    'end_date'       => '',
    'rent_amount'    => '',
    'deposit_amount' => '',
    'notes'          => '',
]);

rules([
    'unit_id'        => ['required', 'exists:units,id'],
    'tenant_id'      => ['required', 'exists:tenants,id'],
    'start_date'     => ['required', 'date'],
    'end_date'       => ['nullable', 'date', 'after:start_date'],
    'rent_amount'    => ['required', 'numeric', 'min:0'],
    'deposit_amount' => ['nullable', 'numeric', 'min:0'],
    'notes'          => ['nullable', 'string'],
]);

$properties = computed(fn() => Property::all());

$vacantUnits = computed(function () {
    if (!$this->property_id) return collect();
    return Unit::where('property_id', $this->property_id)
        ->where('status', 'vacant')
        ->get();
});

$tenants = computed(fn() => Tenant::orderBy('full_name')->get());

// When property changes reset unit selection
$updatedPropertyId = function () {
    $this->unit_id = '';
    $this->rent_amount = '';
};

// Auto-fill rent when unit is selected
$updatedUnitId = function () {
    if ($this->unit_id) {
        $unit = Unit::find($this->unit_id);
        if ($unit) {
            $this->rent_amount = $unit->base_rent;
            $this->deposit_amount = $unit->base_rent;
        }
    }
};

$save = function (LeaseService $leaseService) {
    $validated = $this->validate();

    try {
        $leaseService->createLease($validated);
        session()->flash('success', 'Lease created successfully.');
        $this->redirect(route('leases.index'), navigate: true);
    } catch (\Exception $e) {
        $this->addError('unit_id', $e->getMessage());
    }
};

?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <a href="{{ route('leases.index') }}" wire:navigate
            class="font-inter text-xs uppercase tracking-widest flex items-center gap-1 mb-4
                transition-opacity hover:opacity-70"
            style="color:#9BABB3;">
            ← Back to Leases
        </a>
        <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">
            Create New Lease
        </h1>
        <p class="font-inter text-sm mt-1" style="color:#9BABB3;">
            Assign a tenant to a vacant unit.
        </p>
    </div>

    <div class="rounded-xl p-8" style="background-color:#FFFFFF;">
        <form wire:submit="save" class="space-y-6">

            {{-- Property --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Property</label>
                <select wire:model.live="property_id"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;">
                    <option value="">Select a property...</option>
                    @foreach($this->properties as $property)
                        <option value="{{ $property->id }}">{{ $property->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Unit --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Vacant Unit</label>
                <select wire:model.live="unit_id"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;"
                    {{ !$this->property_id ? 'disabled' : '' }}>
                    <option value="">
                        {{ $this->property_id ? 'Select a unit...' : 'Select a property first' }}
                    </option>
                    @foreach($this->vacantUnits as $unit)
                        <option value="{{ $unit->id }}">
                            Unit {{ $unit->unit_number }} — KES {{ number_format($unit->base_rent, 0) }}
                            ({{ $unit->bedrooms }}bd/{{ $unit->bathrooms }}ba)
                        </option>
                    @endforeach
                </select>
                @error('unit_id')
                    <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                @enderror
                @if($this->property_id && $this->vacantUnits->isEmpty())
                    <p class="font-inter text-xs mt-1" style="color:#9BABB3;">
                        No vacant units in this property.
                    </p>
                @endif
            </div>

            {{-- Tenant --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Tenant</label>
                <select wire:model="tenant_id"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;">
                    <option value="">Select a tenant...</option>
                    @foreach($this->tenants as $tenant)
                        <option value="{{ $tenant->id }}">
                            {{ $tenant->full_name }} — {{ $tenant->phone }}
                        </option>
                    @endforeach
                </select>
                @error('tenant_id')
                    <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Start + End Date --}}
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                        style="color:#9BABB3;">Start Date</label>
                    <input wire:model="start_date" type="date"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                            focus:outline-none focus:ring-0"
                        style="border-color:#E7EFF3; color:#283439;" />
                    @error('start_date')
                        <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                        style="color:#9BABB3;">End Date <span style="color:#C5D0D5;">(optional)</span></label>
                    <input wire:model="end_date" type="date"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                            focus:outline-none focus:ring-0"
                        style="border-color:#E7EFF3; color:#283439;" />
                    @error('end_date')
                        <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Rent + Deposit --}}
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                        style="color:#9BABB3;">Rent Amount (KES)</label>
                    <input wire:model="rent_amount" type="number"
                        placeholder="Auto-filled from unit"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                            focus:outline-none focus:ring-0"
                        style="border-color:#E7EFF3; color:#283439;" />
                    @error('rent_amount')
                        <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                        style="color:#9BABB3;">Deposit Amount (KES)</label>
                    <input wire:model="deposit_amount" type="number"
                        placeholder="Auto-filled from unit"
                        class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                            focus:outline-none focus:ring-0"
                        style="border-color:#E7EFF3; color:#283439;" />
                    @error('deposit_amount')
                        <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Notes <span style="color:#C5D0D5;">(optional)</span></label>
                <textarea wire:model="notes" rows="2"
                    placeholder="Any special lease terms or notes..."
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0 resize-none"
                    style="border-color:#E7EFF3; color:#283439;"></textarea>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between pt-4">
                <a href="{{ route('leases.index') }}" wire:navigate
                    class="font-inter text-sm" style="color:#9BABB3;">Cancel</a>
                <button type="submit"
                    class="font-inter text-xs font-semibold text-white px-6 py-2.5
                        rounded-md transition-opacity hover:opacity-90"
                    style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                    Create Lease
                </button>
            </div>

        </form>
    </div>

</div>