<?php

namespace Database\Seeders;

use App\Models\Lease;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class LeaseSeeder extends Seeder
{
    public function run(): void
    {
        $leases = [
            ['unit' => 'A4',  'tenant' => 'John Ndegwa',   'rent' => 55000, 'deposit' => 55000, 'start' => '2024-01-01', 'end' => '2024-12-31'],
            ['unit' => 'B12', 'tenant' => 'Sarah Otieno',  'rent' => 45000, 'deposit' => 45000, 'start' => '2024-02-01', 'end' => '2025-01-31'],
            ['unit' => 'C1',  'tenant' => 'Moses Kamau',   'rent' => 45000, 'deposit' => 45000, 'start' => '2024-03-01', 'end' => '2025-02-28'],
            ['unit' => 'A2',  'tenant' => 'Lucy Wambui',   'rent' => 35000, 'deposit' => 35000, 'start' => '2024-01-15', 'end' => '2025-01-14'],
            ['unit' => 'B9',  'tenant' => 'David Mutua',   'rent' => 48000, 'deposit' => 48000, 'start' => '2024-04-01', 'end' => '2025-03-31'],
            ['unit' => 'C5',  'tenant' => 'Mary Atieno',   'rent' => 50000, 'deposit' => 50000, 'start' => '2024-01-01', 'end' => '2025-12-31'],
            ['unit' => 'D10', 'tenant' => 'Robert Ochieng','rent' => 60000, 'deposit' => 60000, 'start' => '2024-05-01', 'end' => '2025-04-30'],
            ['unit' => 'C2',  'tenant' => 'Elena Njeri',   'rent' => 45000, 'deposit' => 45000, 'start' => '2024-06-01', 'end' => '2025-05-31'],
            ['unit' => 'A1',  'tenant' => 'Peter Mwangi',  'rent' => 35000, 'deposit' => 35000, 'start' => '2024-03-01', 'end' => '2025-02-28'],
            ['unit' => 'B1',  'tenant' => 'Grace Akinyi',  'rent' => 30000, 'deposit' => 30000, 'start' => '2024-07-01', 'end' => '2025-06-30'],
        ];

        foreach ($leases as $data) {
            $unit   = Unit::where('unit_number', $data['unit'])->first();
            $tenant = Tenant::where('full_name', $data['tenant'])->first();

            Lease::create([
                'unit_id'        => $unit->id,
                'tenant_id'      => $tenant->id,
                'start_date'     => $data['start'],
                'end_date'       => $data['end'],
                'rent_amount'    => $data['rent'],
                'deposit_amount' => $data['deposit'],
                'deposit_status' => 'paid',
                'status'         => 'active',
            ]);
        }
    }
}