<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            ['full_name' => 'John Ndegwa', 'email' => 'john.ndegwa@gmail.com', 'phone' => '+254711000001', 'id_type' => 'national_id', 'id_number' => 'KE12345678', 'emergency_contact' => 'Alice Ndegwa', 'emergency_contact_phone' => '+254722100001'],
            ['full_name' => 'Sarah Otieno', 'email' => 'sarah.otieno@gmail.com', 'phone' => '+254711000002', 'id_type' => 'national_id', 'id_number' => 'KE12345679', 'emergency_contact' => 'Ben Otieno', 'emergency_contact_phone' => '+254722100002'],
            ['full_name' => 'Moses Kamau', 'email' => 'moses.kamau@gmail.com', 'phone' => '+254711000003', 'id_type' => 'national_id', 'id_number' => 'KE12345680', 'emergency_contact' => 'Ruth Kamau', 'emergency_contact_phone' => '+254722100003'],
            ['full_name' => 'Lucy Wambui', 'email' => 'lucy.wambui@gmail.com', 'phone' => '+254711000004', 'id_type' => 'national_id', 'id_number' => 'KE12345681', 'emergency_contact' => 'Peter Wambui', 'emergency_contact_phone' => '+254722100004'],
            ['full_name' => 'David Mutua', 'email' => 'david.mutua@gmail.com', 'phone' => '+254711000005', 'id_type' => 'national_id', 'id_number' => 'KE12345682', 'emergency_contact' => 'Mercy Mutua', 'emergency_contact_phone' => '+254722100005'],
            ['full_name' => 'Mary Atieno', 'email' => 'mary.atieno@gmail.com', 'phone' => '+254711000006', 'id_type' => 'national_id', 'id_number' => 'KE12345683', 'emergency_contact' => 'Joel Atieno', 'emergency_contact_phone' => '+254722100006'],
            ['full_name' => 'Robert Ochieng', 'email' => 'robert.ochieng@gmail.com', 'phone' => '+254711000007', 'id_type' => 'national_id', 'id_number' => 'KE12345684', 'emergency_contact' => 'Janet Ochieng', 'emergency_contact_phone' => '+254722100007'],
            ['full_name' => 'Elena Njeri', 'email' => 'elena.njeri@gmail.com', 'phone' => '+254711000008', 'id_type' => 'national_id', 'id_number' => 'KE12345685', 'emergency_contact' => 'Mark Njeri', 'emergency_contact_phone' => '+254722100008'],
            ['full_name' => 'Peter Mwangi', 'email' => 'peter.mwangi@gmail.com', 'phone' => '+254711000009', 'id_type' => 'national_id', 'id_number' => 'KE12345686', 'emergency_contact' => 'Susan Mwangi', 'emergency_contact_phone' => '+254722100009'],
            ['full_name' => 'Grace Akinyi', 'email' => 'grace.akinyi@gmail.com', 'phone' => '+254711000010', 'id_type' => 'national_id', 'id_number' => 'KE12345687', 'emergency_contact' => 'Tom Akinyi', 'emergency_contact_phone' => '+254722100010'],
            ['full_name' => 'Kevin Kiptoo', 'email' => 'kevin.kiptoo@gmail.com', 'phone' => '+254711000011', 'id_type' => 'passport', 'id_number' => 'P00045711', 'emergency_contact' => 'Faith Kiptoo', 'emergency_contact_phone' => '+254722100011'],
            ['full_name' => 'Nancy Wairimu', 'email' => 'nancy.wairimu@gmail.com', 'phone' => '+254711000012', 'id_type' => 'national_id', 'id_number' => 'KE12345688', 'emergency_contact' => 'James Wairimu', 'emergency_contact_phone' => '+254722100012'],
            ['full_name' => 'Brian Musyoka', 'email' => 'brian.musyoka@gmail.com', 'phone' => '+254711000013', 'id_type' => 'national_id', 'id_number' => 'KE12345689', 'emergency_contact' => 'Anne Musyoka', 'emergency_contact_phone' => '+254722100013'],
            ['full_name' => 'Sharon Chebet', 'email' => 'sharon.chebet@gmail.com', 'phone' => '+254711000014', 'id_type' => 'passport', 'id_number' => 'P00045712', 'emergency_contact' => 'Daniel Chebet', 'emergency_contact_phone' => '+254722100014'],
        ];

        foreach ($tenants as $tenant) {
            Tenant::create($tenant);
        }
    }
}
