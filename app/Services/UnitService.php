<?php

namespace App\Services;

use App\Models\Unit;
use App\Models\Property;

class UnitService
{
    public function create(Property $property, array $data): Unit
    {
        $exists = Unit::where('property_id', $property->id)
            ->where('unit_number', $data['unit_number'])
            ->exists();

        if ($exists) {
            throw new \Exception("Unit {$data['unit_number']} already exists in this property.");
        }

        return Unit::create([
            'property_id' => $property->id,
            'unit_number' => $data['unit_number'],
            'base_rent'   => $data['base_rent'],
            'bedrooms'    => $data['bedrooms'],
            'bathrooms'   => $data['bathrooms'],
            'status'      => $data['status'] ?? 'vacant',
        ]);
    }

    public function update(Unit $unit, array $data): Unit
    {
        $unit->update([
            'unit_number' => $data['unit_number'],
            'base_rent'   => $data['base_rent'],
            'bedrooms'    => $data['bedrooms'],
            'bathrooms'   => $data['bathrooms'],
        ]);

        return $unit;
    }

    public function setMaintenance(Unit $unit): Unit
    {
        if ($unit->status === 'occupied') {
            throw new \Exception("Cannot set an occupied unit to maintenance.");
        }

        $unit->update(['status' => 'maintenance']);
        return $unit;
    }

    public function setVacant(Unit $unit): Unit
    {
        $unit->update(['status' => 'vacant']);
        return $unit;
    }
}