<?php

use App\Models\Property;
use App\Models\Unit;
use App\Services\UnitService;
use function Livewire\Volt\{state, mount, computed};

state([
    'property'    => null,
    'mode'        => 'pattern', // pattern or manual
    'prefix'      => 'A',
    'start'       => 1,
    'end'         => 10,
    'base_rent'   => '',
    'bedrooms'    => 1,
    'bathrooms'   => 1,
    'units'       => [], // manual mode rows
    'preview'     => [],
    'errors_list' => [],
]);

mount(function (Property $property) {
    $this->property  = $property;
    $this->base_rent = '';
    $this->units     = [
        ['unit_number' => '', 'base_rent' => '', 'bedrooms' => 1, 'bathrooms' => 1],
    ];
});

$generatePreview = function () {
    $this->preview     = [];
    $this->errors_list = [];

    if ($this->mode === 'pattern') {
        if (empty($this->prefix) || empty($this->start) || empty($this->end)) {
            $this->errors_list[] = 'Please fill in prefix, start and end number.';
            return;
        }
        if ((int)$this->end < (int)$this->start) {
            $this->errors_list[] = 'End number must be greater than start number.';
            return;
        }
        if ((int)$this->end - (int)$this->start > 49) {
            $this->errors_list[] = 'Maximum 50 units per batch.';
            return;
        }
        for ($i = (int)$this->start; $i <= (int)$this->end; $i++) {
            $this->preview[] = [
                'unit_number' => $this->prefix . $i,
                'base_rent'   => $this->base_rent,
                'bedrooms'    => $this->bedrooms,
                'bathrooms'   => $this->bathrooms,
            ];
        }
    }
};

$addRow = function () {
    $this->units[] = ['unit_number' => '', 'base_rent' => '', 'bedrooms' => 1, 'bathrooms' => 1];
};

$removeRow = function ($index) {
    array_splice($this->units, $index, 1);
    $this->units = array_values($this->units);
};

$saveBatch = function (UnitService $unitService) {
    $this->errors_list = [];
    $toCreate = $this->mode === 'pattern' ? $this->preview : $this->units;

    if (empty($toCreate)) {
        $this->errors_list[] = 'Nothing to save. Generate a preview first.';
        return;
    }

    $created = 0;
    $skipped = 0;

    foreach ($toCreate as $row) {
        if (empty($row['unit_number']) || empty($row['base_rent'])) {
            $skipped++;
            continue;
        }

        // Check duplicate
        $exists = Unit::where('property_id', $this->property->id)
            ->where('unit_number', $row['unit_number'])
            ->exists();

        if ($exists) {
            $this->errors_list[] = "Unit {$row['unit_number']} already exists — skipped.";
            $skipped++;
            continue;
        }

        Unit::create([
            'property_id' => $this->property->id,
            'unit_number' => $row['unit_number'],
            'base_rent'   => $row['base_rent'],
            'bedrooms'    => $row['bedrooms'],
            'bathrooms'   => $row['bathrooms'],
            'status'      => 'vacant',
        ]);

        $created++;
    }

    if ($created > 0) {
        session()->flash('success', "{$created} units created successfully." .
            ($skipped > 0 ? " {$skipped} skipped." : ''));
        $this->redirect(route('properties.show', $this->property), navigate: true);
    }
};

?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <a href="{{ route('properties.show', $property) }}" wire:navigate
            class="font-inter text-xs uppercase tracking-widest flex items-center gap-1 mb-4
                transition-opacity hover:opacity-70" style="color:#9BABB3;">
            ← Back to {{ $property->name }}
        </a>
        <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">
            Add Units in Batch
        </h1>
        <p class="font-inter text-sm mt-1" style="color:#9BABB3;">
            {{ $property->name }} — {{ $property->location }}
        </p>
    </div>

    {{-- Error list --}}
    @if(!empty($errors_list))
    <div class="mb-6 px-4 py-3 rounded-md font-inter text-sm"
        style="background-color:#FDECEA; color:#9F403D;">
        @foreach($errors_list as $err)
            <p>{{ $err }}</p>
        @endforeach
    </div>
    @endif

    {{-- Mode toggle --}}
    <div class="flex gap-2 mb-6">
        <button wire:click="$set('mode', 'pattern')"
            class="font-inter text-xs font-semibold px-4 py-2 rounded-md transition-all"
            style="background: {{ $mode === 'pattern' ? 'linear-gradient(135deg, #585E6C, #4C5260)' : '#FFFFFF' }};
                   color: {{ $mode === 'pattern' ? '#FFFFFF' : '#9BABB3' }};">
            Pattern mode
        </button>
        <button wire:click="$set('mode', 'manual')"
            class="font-inter text-xs font-semibold px-4 py-2 rounded-md transition-all"
            style="background: {{ $mode === 'manual' ? 'linear-gradient(135deg, #585E6C, #4C5260)' : '#FFFFFF' }};
                   color: {{ $mode === 'manual' ? '#FFFFFF' : '#9BABB3' }};">
            Manual mode
        </button>
    </div>

    {{-- Pattern mode --}}
    @if($mode === 'pattern')
    <div class="rounded-xl p-8 mb-6" style="background-color:#FFFFFF;">
        <p class="font-manrope text-sm font-semibold mb-6" style="color:#283439;">
            Pattern Generator
        </p>
        <p class="font-inter text-xs mb-6" style="color:#9BABB3;">
            Generate unit numbers automatically. For example prefix "A", start 1, end 10 creates A1 through A10.
        </p>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 mb-6">
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Prefix</label>
                <input wire:model="prefix" type="text" placeholder="A"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
            </div>
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Start number</label>
                <input wire:model="start" type="number" placeholder="1" min="1"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
            </div>
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">End number</label>
                <input wire:model="end" type="number" placeholder="10" min="1"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
            </div>
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Base Rent (KES)</label>
                <input wire:model="base_rent" type="number" placeholder="35000"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Bedrooms (all units)</label>
                <select wire:model="bedrooms"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                    style="border-color:#E7EFF3; color:#283439;">
                    @foreach(range(1,6) as $n)
                        <option value="{{ $n }}">{{ $n }} Bedroom{{ $n > 1 ? 's' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Bathrooms (all units)</label>
                <select wire:model="bathrooms"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                    style="border-color:#E7EFF3; color:#283439;">
                    @foreach(range(1,4) as $n)
                        <option value="{{ $n }}">{{ $n }} Bathroom{{ $n > 1 ? 's' : '' }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <button wire:click="generatePreview"
            class="font-inter text-xs font-semibold px-5 py-2.5 rounded-md transition-opacity hover:opacity-90"
            style="background-color:#E7EFF3; color:#585E6C;">
            Generate Preview
        </button>
    </div>

    {{-- Preview table --}}
    @if(!empty($preview))
    <div class="rounded-xl mb-6 overflow-hidden" style="background-color:#FFFFFF;">
        <div class="px-6 py-4 flex items-center justify-between"
            style="border-bottom: 1px solid #EFF4F7;">
            <p class="font-manrope text-sm font-semibold" style="color:#283439;">
                Preview — {{ count($preview) }} units
            </p>
            <p class="font-inter text-xs" style="color:#9BABB3;">
                Review before saving
            </p>
        </div>
        <div class="grid grid-cols-4 px-6 py-3" style="background-color:#EFF4F7;">
            <p class="font-inter text-xs font-medium uppercase tracking-widest" style="color:#9BABB3;">Unit</p>
            <p class="font-inter text-xs font-medium uppercase tracking-widest" style="color:#9BABB3;">Rent</p>
            <p class="font-inter text-xs font-medium uppercase tracking-widest" style="color:#9BABB3;">Beds</p>
            <p class="font-inter text-xs font-medium uppercase tracking-widest" style="color:#9BABB3;">Baths</p>
        </div>
        @foreach($preview as $row)
        <div class="grid grid-cols-4 px-6 py-3 font-inter text-sm"
            style="color:#283439; border-bottom: 0.5px solid #F7FAFC;">
            <p>{{ $row['unit_number'] }}</p>
            <p>@money($row['base_rent'])</p>
            <p>{{ $row['bedrooms'] }}</p>
            <p>{{ $row['bathrooms'] }}</p>
        </div>
        @endforeach
    </div>
    @endif
    @endif

    {{-- Manual mode --}}
    @if($mode === 'manual')
    <div class="rounded-xl p-8 mb-6" style="background-color:#FFFFFF;">
        <p class="font-manrope text-sm font-semibold mb-2" style="color:#283439;">
            Manual Entry
        </p>
        <p class="font-inter text-xs mb-6" style="color:#9BABB3;">
            Add each unit individually. Useful when units have different rents or layouts.
        </p>

        {{-- Header --}}
        <div class="grid grid-cols-5 gap-3 mb-3">
            <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Unit no.</p>
            <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Base rent</p>
            <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Beds</p>
            <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Baths</p>
            <p></p>
        </div>

        @foreach($units as $i => $unit)
        <div class="grid grid-cols-5 gap-3 mb-3 items-center">
            <input wire:model="units.{{ $i }}.unit_number" type="text" placeholder="A1"
                class="border-0 border-b py-2 font-inter text-sm bg-transparent
                    focus:outline-none focus:ring-0"
                style="border-color:#E7EFF3; color:#283439;" />
            <input wire:model="units.{{ $i }}.base_rent" type="number" placeholder="35000"
                class="border-0 border-b py-2 font-inter text-sm bg-transparent
                    focus:outline-none focus:ring-0"
                style="border-color:#E7EFF3; color:#283439;" />
            <select wire:model="units.{{ $i }}.bedrooms"
                class="border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                style="border-color:#E7EFF3; color:#283439;">
                @foreach(range(1,6) as $n)
                    <option value="{{ $n }}">{{ $n }}</option>
                @endforeach
            </select>
            <select wire:model="units.{{ $i }}.bathrooms"
                class="border-0 border-b py-2 font-inter text-sm bg-transparent focus:outline-none"
                style="border-color:#E7EFF3; color:#283439;">
                @foreach(range(1,4) as $n)
                    <option value="{{ $n }}">{{ $n }}</option>
                @endforeach
            </select>
            @if(count($units) > 1)
            <button wire:click="removeRow({{ $i }})"
                class="font-inter text-xs px-2 py-1 rounded"
                style="color:#9F403D; background-color:#FDECEA;">
                Remove
            </button>
            @else
            <div></div>
            @endif
        </div>
        @endforeach

        <button wire:click="addRow"
            class="mt-4 font-inter text-xs font-medium px-4 py-2 rounded-md"
            style="background-color:#E7EFF3; color:#585E6C;">
            + Add Row
        </button>
    </div>
    @endif

    {{-- Save button --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('properties.show', $property) }}" wire:navigate
            class="font-inter text-sm" style="color:#9BABB3;">Cancel</a>
        <button wire:click="saveBatch"
            class="font-inter text-xs font-semibold text-white px-6 py-2.5
                rounded-md transition-opacity hover:opacity-90"
            style="background: linear-gradient(135deg, #585E6C, #4C5260);">
            Save {{ count($mode === 'pattern' ? $preview : $units) }} Units
        </button>
    </div>

</div>
