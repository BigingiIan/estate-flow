<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            'Sunshine Apartments' => [
                ['unit_number' => 'A1', 'base_rent' => 38000, 'status' => 'occupied', 'bedrooms' => 2, 'bathrooms' => 1],
                ['unit_number' => 'A2', 'base_rent' => 40000, 'status' => 'occupied', 'bedrooms' => 2, 'bathrooms' => 2],
                ['unit_number' => 'A3', 'base_rent' => 42000, 'status' => 'vacant', 'bedrooms' => 3, 'bathrooms' => 2],
                ['unit_number' => 'B1', 'base_rent' => 30000, 'status' => 'occupied', 'bedrooms' => 1, 'bathrooms' => 1],
                ['unit_number' => 'B2', 'base_rent' => 31500, 'status' => 'maintenance', 'bedrooms' => 1, 'bathrooms' => 1],
            ],
            'Azure Heights' => [
                ['unit_number' => 'A4', 'base_rent' => 56000, 'status' => 'occupied', 'bedrooms' => 3, 'bathrooms' => 2],
                ['unit_number' => 'B12', 'base_rent' => 47000, 'status' => 'occupied', 'bedrooms' => 2, 'bathrooms' => 2],
                ['unit_number' => 'C1', 'base_rent' => 45500, 'status' => 'occupied', 'bedrooms' => 2, 'bathrooms' => 2],
                ['unit_number' => 'C2', 'base_rent' => 45500, 'status' => 'vacant', 'bedrooms' => 2, 'bathrooms' => 1],
                ['unit_number' => 'D10', 'base_rent' => 62000, 'status' => 'occupied', 'bedrooms' => 3, 'bathrooms' => 2],
                ['unit_number' => 'E3', 'base_rent' => 52000, 'status' => 'maintenance', 'bedrooms' => 2, 'bathrooms' => 2],
            ],
            'Riverside Plaza' => [
                ['unit_number' => 'B3', 'base_rent' => 49000, 'status' => 'vacant', 'bedrooms' => 2, 'bathrooms' => 1],
                ['unit_number' => 'B9', 'base_rent' => 50000, 'status' => 'occupied', 'bedrooms' => 2, 'bathrooms' => 2],
                ['unit_number' => 'C5', 'base_rent' => 53000, 'status' => 'occupied', 'bedrooms' => 2, 'bathrooms' => 2],
                ['unit_number' => 'D1', 'base_rent' => 72000, 'status' => 'vacant', 'bedrooms' => 4, 'bathrooms' => 3],
                ['unit_number' => 'D2', 'base_rent' => 71000, 'status' => 'vacant', 'bedrooms' => 4, 'bathrooms' => 3],
            ],
            'Maple Court' => [
                ['unit_number' => 'M1', 'base_rent' => 28000, 'status' => 'occupied', 'bedrooms' => 1, 'bathrooms' => 1],
                ['unit_number' => 'M2', 'base_rent' => 29500, 'status' => 'vacant', 'bedrooms' => 1, 'bathrooms' => 1],
                ['unit_number' => 'M3', 'base_rent' => 33000, 'status' => 'vacant', 'bedrooms' => 2, 'bathrooms' => 1],
                ['unit_number' => 'M4', 'base_rent' => 35000, 'status' => 'maintenance', 'bedrooms' => 2, 'bathrooms' => 2],
            ],
            'Greenview Residency' => [
                ['unit_number' => 'G1', 'base_rent' => 36000, 'status' => 'occupied', 'bedrooms' => 2, 'bathrooms' => 1],
                ['unit_number' => 'G2', 'base_rent' => 36500, 'status' => 'vacant', 'bedrooms' => 2, 'bathrooms' => 1],
                ['unit_number' => 'G3', 'base_rent' => 41000, 'status' => 'occupied', 'bedrooms' => 3, 'bathrooms' => 2],
                ['unit_number' => 'G4', 'base_rent' => 42000, 'status' => 'maintenance', 'bedrooms' => 3, 'bathrooms' => 2],
            ],
        ];

        foreach ($units as $propertyName => $propertyUnits) {
            $property = Property::where('name', $propertyName)->firstOrFail();

            foreach ($propertyUnits as $unit) {
                Unit::create([
                    ...$unit,
                    'property_id' => $property->id,
                ]);
            }
        }
    }
}
