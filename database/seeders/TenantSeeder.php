<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            [
                'full_name'         => 'John Ndegwa',
                'email'             => 'john.ndegwa@gmail.com',
                'phone'             => '+254711000001',
                'id_type'           => 'national_id',
                'id_number'         => 'KE12345678',
                'emergency_contact' => '+254722000001',
            ],
            [
                'full_name'         => 'Sarah Otieno',
                'email'             => 'sarah.otieno@gmail.com',
                'phone'             => '+254711000002',
                'id_type'           => 'national_id',
                'id_number'         => 'KE12345679',
                'emergency_contact' => '+254722000002',
            ],
            [
                'full_name'         => 'Moses Kamau',
                'email'             => 'moses.kamau@gmail.com',
                'phone'             => '+254711000003',
                'id_type'           => 'national_id',
                'id_number'         => 'KE12345680',
                'emergency_contact' => '+254722000003',
            ],
            [
                'full_name'         => 'Lucy Wambui',
                'email'             => 'lucy.wambui@gmail.com',
                'phone'             => '+254711000004',
                'id_type'           => 'national_id',
                'id_number'         => 'KE12345681',
                'emergency_contact' => '+254722000004',
            ],
            [
                'full_name'         => 'David Mutua',
                'email'             => 'david.mutua@gmail.com',
                'phone'             => '+254711000005',
                'id_type'           => 'national_id',
                'id_number'         => 'KE12345682',
                'emergency_contact' => '+254722000005',
            ],
            [
                'full_name'         => 'Mary Atieno',
                'email'             => 'mary.atieno@gmail.com',
                'phone'             => '+254711000006',
                'id_type'           => 'national_id',
                'id_number'         => 'KE12345683',
                'emergency_contact' => '+254722000006',
            ],
            [
                'full_name'         => 'Robert Ochieng',
                'email'             => 'robert.ochieng@gmail.com',
                'phone'             => '+254711000007',
                'id_type'           => 'national_id',
                'id_number'         => 'KE12345684',
                'emergency_contact' => '+254722000007',
            ],
            [
                'full_name'         => 'Elena Njeri',
                'email'             => 'elena.njeri@gmail.com',
                'phone'             => '+254711000008',
                'id_type'           => 'national_id',
                'id_number'         => 'KE12345685',
                'emergency_contact' => '+254722000008',
            ],
            [
                'full_name'         => 'Peter Mwangi',
                'email'             => 'peter.mwangi@gmail.com',
                'phone'             => '+254711000009',
                'id_type'           => 'national_id',
                'id_number'         => 'KE12345686',
                'emergency_contact' => '+254722000009',
            ],
            [
                'full_name'         => 'Grace Akinyi',
                'email'             => 'grace.akinyi@gmail.com',
                'phone'             => '+254711000010',
                'id_type'           => 'national_id',
                'id_number'         => 'KE12345687',
                'emergency_contact' => '+254722000010',
            ],
        ];

        foreach ($tenants as $tenant) {
            Tenant::create($tenant);
        }
    }
}