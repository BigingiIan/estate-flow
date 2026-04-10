<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Support\Facades\Auth;

class PropertyService
{
    public function create(array $data): Property
    {
        return Property::create([
            'user_id'     => Auth::id(),
            'name'        => $data['name'],
            'location'    => $data['location'],
            'description' => $data['description'] ?? null,
        ]);
    }

    public function update(Property $property, array $data): Property
    {
        $property->update([
            'name'        => $data['name'],
            'location'    => $data['location'],
            'description' => $data['description'] ?? null,
        ]);

        return $property;
    }

    public function delete(Property $property): void
    {
        // Only delete if no active leases exist under this property
        $hasActiveLeases = $property->units()
            ->whereHas('leases', fn($q) => $q->where('status', 'active'))
            ->exists();

        if ($hasActiveLeases) {
            throw new \Exception("Cannot delete a property with active leases.");
        }

        $property->delete();
    }

    public function getSummary(Property $property): array
    {
        $units        = $property->units;
        $totalUnits   = $units->count();
        $vacantUnits  = $units->where('status', 'vacant')->count();
        $occupiedUnits = $units->where('status', 'occupied')->count();

        return [
            'total_units'   => $totalUnits,
            'vacant_units'  => $vacantUnits,
            'occupied_units' => $occupiedUnits,
            'occupancy_rate' => $totalUnits > 0
                ? round(($occupiedUnits / $totalUnits) * 100)
                : 0,
        ];
    }
}