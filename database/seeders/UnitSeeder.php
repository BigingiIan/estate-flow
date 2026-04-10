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
            // Sunshine Apartments
            'Sunshine Apartments' => [
                ['unit_number' => 'A1', 'base_rent' => 35000, 'status' => 'occupied', 'bedrooms' => 2, 'bathrooms' => 1],
                ['unit_number' => 'A2', 'base_rent' => 35000, 'status' => 'occupied', 'bedrooms' => 2, 'bathrooms' => 1],
                ['unit_number' => 'A3', 'base_rent' => 40000, 'status' => 'vacant',   'bedrooms' => 3, 'bathrooms' => 2],
                ['unit_number' => 'B1', 'base_rent' => 30000, 'status' => 'occupied', 'bedrooms' => 1, 'bathrooms' => 1],
                ['unit_number' => 'B2', 'base_rent' => 30000, 'status' => 'occupied', 'bedrooms' => 1, 'bathrooms' => 1],
            ],
            // Azure Heights
            'Azure Heights' => [
                ['unit_number' => 'A4',  'base_rent' => 55000, 'status' => 'occupied', 'bedrooms' => 3, 'bathrooms' => 2],
                ['unit_number' => 'B12', 'base_rent' => 45000, 'status' => 'occupied', 'bedrooms' => 2, 'bathrooms' => 2],
                ['unit_number' => 'C1',  'base_rent' => 45000, 'status' => 'occupied', 'bedrooms' => 2, 'bathrooms' => 2],
                ['unit_number' => 'D10', 'base_rent' => 60000, 'status' => 'occupied', 'bedrooms' => 3, 'bathrooms' => 2],
                ['unit_number' => 'C2',  'base_rent' => 45000, 'status' => 'occupied', 'bedrooms' => 2, 'bathrooms' => 1],
            ],
            // Riverside Plaza
            'Riverside Plaza' => [
                ['unit_number' => 'C5',  'base_rent' => 50000, 'status' => 'occupied', 'bedrooms' => 2, 'bathrooms' => 2],
                ['unit_number' => 'D1',  'base_rent' => 70000, 'status' => 'vacant',   'bedrooms' => 4, 'bathrooms' => 3],
                ['unit_number' => 'D2',  'base_rent' => 70000, 'status' => 'vacant',   'bedrooms' => 4, 'bathrooms' => 3],
                ['unit_number' => 'B9',  'base_rent' => 48000, 'status' => 'occupied', 'bedrooms' => 2, 'bathrooms' => 1],
                ['unit_number' => 'B3',  'base_rent' => 48000, 'status' => 'occupied', 'bedrooms' => 2, 'bathrooms' => 1],
            ],
        ];

        foreach ($units as $propertyName => $propertyUnits) {
            $property = Property::where('name', $propertyName)->first();
            foreach ($propertyUnits as $unit) {
                Unit::create(array_merge($unit, ['property_id' => $property->id]));
            }
        }
    }
}