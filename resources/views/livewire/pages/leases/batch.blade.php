<?php

use App\Models\Property;
use App\Models\Unit;
use App\Models\Tenant;
use App\Models\Lease;
use App\Services\LeaseService;
use function Livewire\Volt\{state, mount, computed};

state([
    'property_id' => '',
    'start_date'  => '',
    'end_date'    => '',
    'rows'        => [], // [{unit_id, tenant_id, rent_amount, deposit_amount}]
    'errors_list' => [],
]);

mount(function () {
    $this->start_date = now()->format('Y-m-d');
    $this->end_date   = now()->addYear()->format('Y-m-d');
});

$properties = computed(fn() => Property::all());

$vacantUnits = computed(function () {
    if (!$this->property_id) return collect();
    return Unit::where('property_id', $this->property_id)
        ->where('status', 'vacant')
        ->get();
});

$tenants = computed(fn() => Tenant::orderBy('full_name')->get());

$updatedPropertyId = function () {
    // Build rows from all vacant units in this property
    $this->rows = Unit::where('property_id', $this->property_id)
        ->where('status', 'vacant')
        ->get()
        ->map(fn($unit) => [
            'unit_id'        => $unit->id,
            'unit_number'    => $unit->unit_number,
            'base_rent'      => $unit->base_rent,
            'tenant_id'      => '',
            'rent_amount'    => $unit->base_rent,
            'deposit_amount' => $unit->base_rent,
        ])
        ->toArray();
};

$saveBatch = function (LeaseService $leaseService) {
    $this->errors_list = [];

    if (empty($this->property_id)) {
        $this->errors_list[] = 'Select a property first.';
        return;
    }

    if (empty($this->start_date)) {
        $this->errors_list[] = 'Start date is required.';
        return;
    }

    $created = 0;
    $skipped = 0;

    foreach ($this->rows as $row) {
        if (empty($row['tenant_id'])) {
            $skipped++;
            continue;
        }

        // Check tenant doesn't already have an active lease
        $hasActiveLease = Lease::where('tenant_id', $row['tenant_id'])
            ->where('status', 'active')
            ->exists();

        if ($hasActiveLease) {
            $tenant = Tenant::find($row['tenant_id']);
            $this->errors_list[] = "{$tenant->full_name} already has an active lease — skipped.";
            $skipped++;
            continue;
        }

        try {
            $leaseService->createLease([
                'unit_id'        => $row['unit_id'],
                'tenant_id'      => $row['tenant_id'],
                'start_date'     => $this->start_date,
                'end_date'       => $this->end_date ?: null,
                'rent_amount'    => $row['rent_amount'],
                'deposit_amount' => $row['deposit_amount'],
            ]);
            $created++;
        } catch (\Exception $e) {
            $this->errors_list[] = "Unit {$row['unit_number']}: {$e->getMessage()}";
            $skipped++;
        }
    }

    if ($created > 0) {
        session()->flash('success', "{$created} leases created successfully." .
            ($skipped > 0 ? " {$skipped} skipped." : ''));
        $this->redirect(route('leases.index'), navigate: true);
    } elseif (empty($this->errors_list)) {
        $this->errors_list[] = 'No tenants assigned. Add at least one tenant to create leases.';
    }
};

?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <a href="{{ route('leases.index') }}" wire:navigate
            class="font-inter text-xs uppercase tracking-widest flex items-center gap-1 mb-4
                transition-opacity hover:opacity-70" style="color:#9BABB3;">
            ← Back to Leases
        </a>
        <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">
            Batch Create Leases
        </h1>
        <p class="font-inter text-sm mt-1" style="color:#9BABB3;">
            Assign multiple tenants to vacant units in the same property at once.
            Only applicable to units within a single property.
        </p>
    </div>

    @if(!empty($errors_list))
    <div class="mb-6 px-4 py-3 rounded-md" style="background-color:#FDECEA;">
        @foreach($errors_list as $err)
            <p class="font-inter text-xs" style="color:#9F403D;">{{ $err }}</p>
        @endforeach
    </div>
    @endif

    {{-- Property + Dates --}}
    <div class="rounded-xl p-8 mb-6" style="background-color:#FFFFFF;">
        <p class="font-manrope text-sm font-semibold mb-6" style="color:#283439;">
            Step 1 — Select Property &amp; Lease Dates
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Property</label>
                <select wire:model.live="property_id"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                    style="border-color:#E7EFF3; color:#283439;">
                    <option value="">Select property...</option>
                    @foreach($this->properties as $property)
                        <option value="{{ $property->id }}">{{ $property->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Start Date</label>
                <input wire:model="start_date" type="date"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                    style="border-color:#E7EFF3; color:#283439;" />
            </div>
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">End Date <span style="color:#C5D0D5;">(optional)</span></label>
                <input wire:model="end_date" type="date"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                    style="border-color:#E7EFF3; color:#283439;" />
            </div>
        </div>
    </div>

    {{-- Unit-tenant assignment table --}}
    @if($this->property_id && !empty($this->rows))
    <div class="rounded-xl mb-6" style="background-color:#FFFFFF;">
        <div class="px-6 py-4 flex items-center justify-between"
            style="border-bottom: 1px solid #EFF4F7;">
            <p class="font-manrope text-sm font-semibold" style="color:#283439;">
                Step 2 — Assign Tenants to Units
            </p>
            <p class="font-inter text-xs" style="color:#9BABB3;">
                {{ count($this->rows) }} vacant unit{{ count($this->rows) !== 1 ? 's' : '' }} in this property.
                Leave tenant blank to skip a unit.
            </p>
        </div>

        {{-- Table header --}}
        <div class="grid grid-cols-5 gap-4 px-6 py-3" style="background-color:#EFF4F7;">
            <p class="font-inter text-xs font-medium uppercase tracking-widest" style="color:#9BABB3;">Unit</p>
            <p class="font-inter text-xs font-medium uppercase tracking-widest col-span-2" style="color:#9BABB3;">Tenant</p>
            <p class="font-inter text-xs font-medium uppercase tracking-widest" style="color:#9BABB3;">Rent (KES)</p>
            <p class="font-inter text-xs font-medium uppercase tracking-widest" style="color:#9BABB3;">Deposit (KES)</p>
        </div>

        @foreach($rows as $i => $row)
        <div class="grid grid-cols-5 gap-4 px-6 py-4 items-center"
            style="border-bottom: 0.5px solid #F7FAFC;">
            <div>
                <p class="font-inter text-sm font-medium" style="color:#283439;">
                    Unit {{ $row['unit_number'] }}
                </p>
                <p class="font-inter text-xs" style="color:#9BABB3;">
                    KES {{ number_format($row['base_rent'], 0) }}/mo
                </p>
            </div>
            <div class="col-span-2">
                <select wire:model="rows.{{ $i }}.tenant_id"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                    style="border-color:#E7EFF3; color:#283439;">
                    <option value="">— Skip this unit —</option>
                    @foreach($this->tenants as $tenant)
                        <option value="{{ $tenant->id }}">
                            {{ $tenant->full_name }} ({{ $tenant->phone }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <input wire:model="rows.{{ $i }}.rent_amount" type="number"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                    style="border-color:#E7EFF3; color:#283439;" />
            </div>
            <div>
                <input wire:model="rows.{{ $i }}.deposit_amount" type="number"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                    style="border-color:#E7EFF3; color:#283439;" />
            </div>
        </div>
        @endforeach
    </div>

    <div class="flex items-center justify-between">
        <p class="font-inter text-xs" style="color:#9BABB3;">
            Only tenants without an active lease are eligible.
        </p>
        <button wire:click="saveBatch"
            class="font-inter text-xs font-semibold text-white px-6 py-2.5
                rounded-md transition-opacity hover:opacity-90"
            style="background: linear-gradient(135deg, #585E6C, #4C5260);">
            Create Leases
        </button>
    </div>

    @elseif($this->property_id && empty($this->rows))
    <div class="rounded-xl p-8 text-center" style="background-color:#FFFFFF;">
        <p class="font-inter text-sm" style="color:#9BABB3;">
            No vacant units in this property. All units are occupied or under maintenance.
        </p>
    </div>
    @endif

</div>