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
            ['unit' => 'A1', 'tenant' => 'John Ndegwa', 'rent' => 38000, 'deposit' => 38000, 'start' => '2026-01-01', 'end' => '2026-12-31', 'deposit_status' => 'paid', 'status' => 'active', 'notes' => 'Pays consistently via M-Pesa.'],
            ['unit' => 'A2', 'tenant' => 'Sarah Otieno', 'rent' => 40000, 'deposit' => 40000, 'start' => '2025-11-01', 'end' => '2026-10-31', 'deposit_status' => 'paid', 'status' => 'active', 'notes' => 'Renewed for another 12 months.'],
            ['unit' => 'B1', 'tenant' => 'Moses Kamau', 'rent' => 30000, 'deposit' => 30000, 'start' => '2026-03-15', 'end' => '2027-03-14', 'deposit_status' => 'pending', 'status' => 'active', 'notes' => 'Deposit being cleared in two installments.'],
            ['unit' => 'A4', 'tenant' => 'Lucy Wambui', 'rent' => 56000, 'deposit' => 56000, 'start' => '2025-08-01', 'end' => '2026-07-31', 'deposit_status' => 'paid', 'status' => 'active', 'notes' => 'Corporate lease for a regional manager.'],
            ['unit' => 'B12', 'tenant' => 'David Mutua', 'rent' => 47000, 'deposit' => 47000, 'start' => '2026-02-01', 'end' => '2026-05-31', 'deposit_status' => 'paid', 'status' => 'active', 'notes' => 'Renewal decision due next month.'],
            ['unit' => 'C1', 'tenant' => 'Mary Atieno', 'rent' => 45500, 'deposit' => 45500, 'start' => '2025-06-01', 'end' => '2026-05-15', 'deposit_status' => 'paid', 'status' => 'active', 'notes' => 'Lease approaching expiry window.'],
            ['unit' => 'D10', 'tenant' => 'Robert Ochieng', 'rent' => 62000, 'deposit' => 62000, 'start' => '2026-04-01', 'end' => '2027-03-31', 'deposit_status' => 'paid', 'status' => 'active', 'notes' => 'Recently onboarded tenant.'],
            ['unit' => 'B9', 'tenant' => 'Elena Njeri', 'rent' => 50000, 'deposit' => 50000, 'start' => '2025-10-01', 'end' => '2026-09-30', 'deposit_status' => 'paid', 'status' => 'active', 'notes' => 'Tenant requested parking allocation.'],
            ['unit' => 'C5', 'tenant' => 'Peter Mwangi', 'rent' => 53000, 'deposit' => 53000, 'start' => '2025-12-01', 'end' => '2026-11-30', 'deposit_status' => 'paid', 'status' => 'active', 'notes' => 'Occasionally pays by bank transfer.'],
            ['unit' => 'M1', 'tenant' => 'Grace Akinyi', 'rent' => 28000, 'deposit' => 28000, 'start' => '2026-01-15', 'end' => '2027-01-14', 'deposit_status' => 'paid', 'status' => 'active', 'notes' => 'Good long-term fit for the estate.'],
            ['unit' => 'G1', 'tenant' => 'Kevin Kiptoo', 'rent' => 36000, 'deposit' => 36000, 'start' => '2026-02-15', 'end' => '2027-02-14', 'deposit_status' => 'paid', 'status' => 'active', 'notes' => 'Prefers receipts sent by email.'],
            ['unit' => 'G3', 'tenant' => 'Nancy Wairimu', 'rent' => 41000, 'deposit' => 41000, 'start' => '2025-09-01', 'end' => '2026-08-31', 'deposit_status' => 'paid', 'status' => 'active', 'notes' => 'Family tenant with stable history.'],
            ['unit' => 'D2', 'tenant' => 'Brian Musyoka', 'rent' => 71000, 'deposit' => 71000, 'start' => '2024-05-01', 'end' => '2025-04-30', 'deposit_status' => 'refunded', 'status' => 'expired', 'notes' => 'Previous tenant moved out at end of term.'],
            ['unit' => 'B3', 'tenant' => 'Sharon Chebet', 'rent' => 49000, 'deposit' => 49000, 'start' => '2025-01-01', 'end' => '2025-10-15', 'deposit_status' => 'refunded', 'status' => 'terminated', 'notes' => 'Lease terminated after relocation request.'],
        ];

        foreach ($leases as $data) {
            $unit = Unit::where('unit_number', $data['unit'])->firstOrFail();
            $tenant = Tenant::where('full_name', $data['tenant'])->firstOrFail();

            Lease::create([
                'unit_id' => $unit->id,
                'tenant_id' => $tenant->id,
                'start_date' => $data['start'],
                'end_date' => $data['end'],
                'rent_amount' => $data['rent'],
                'deposit_amount' => $data['deposit'],
                'deposit_status' => $data['deposit_status'],
                'status' => $data['status'],
                'notes' => $data['notes'],
            ]);
        }
    }
}
