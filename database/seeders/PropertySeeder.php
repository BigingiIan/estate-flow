<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', 'ian@estateflow.co.ke')->firstOrFail();

        $properties = [
            [
                'name' => 'Sunshine Apartments',
                'location' => 'Westlands, Nairobi',
                'description' => 'A mid-rise apartment block popular with young professionals working in Westlands.',
            ],
            [
                'name' => 'Azure Heights',
                'location' => 'Kilimani, Nairobi',
                'description' => 'Modern family apartments with a mix of two and three-bedroom units.',
            ],
            [
                'name' => 'Riverside Plaza',
                'location' => 'Riverside Drive, Nairobi',
                'description' => 'Executive residences with larger units suited for long-term tenants.',
            ],
            [
                'name' => 'Maple Court',
                'location' => 'Syokimau, Machakos',
                'description' => 'A commuter-friendly gated development with affordable starter units.',
            ],
            [
                'name' => 'Greenview Residency',
                'location' => 'Thindigua, Kiambu',
                'description' => 'A fast-growing suburban property with a blend of occupied and available homes.',
            ],
        ];

        foreach ($properties as $property) {
            Property::create([
                'user_id' => $owner->id,
                'name' => $property['name'],
                'location' => $property['location'],
                'description' => $property['description'],
            ]);
        }
    }
}
