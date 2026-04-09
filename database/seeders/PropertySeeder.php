<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        $properties = [
            [
                'name'        => 'Sunshine Apartments',
                'location'    => 'Westlands, Nairobi',
                'description' => 'Modern apartments in the heart of Westlands.',
            ],
            [
                'name'        => 'Azure Heights',
                'location'    => 'Kilimani, Nairobi',
                'description' => 'Upmarket residential units in Kilimani.',
            ],
            [
                'name'        => 'Riverside Plaza',
                'location'    => 'Riverside Drive, Nairobi',
                'description' => 'Serviced apartments along Riverside Drive.',
            ],
        ];

        foreach ($properties as $property) {
            Property::create([
                'user_id'     => $user->id,
                'name'        => $property['name'],
                'location'    => $property['location'],
                'description' => $property['description'],
            ]);
        }
    }
}