<?php

namespace Database\Seeders;

use App\Models\Lease;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $leases = Lease::all();

        foreach ($leases as $lease) {
            // Paid rent this month for most tenants
            if ($lease->tenant->full_name !== 'John Ndegwa'
                && $lease->tenant->full_name !== 'Sarah Otieno'
                && $lease->tenant->full_name !== 'Moses Kamau') {

                Transaction::create([
                    'lease_id'       => $lease->id,
                    'type'           => 'rent',
                    'amount'         => $lease->rent_amount,
                    'reference_code' => 'TXN-' . strtoupper(Str::random(6)),
                    'payment_method' => 'mpesa',
                    'paid_at'        => now()->startOfMonth()->addDays(rand(1, 5)),
                    'notes'          => 'Monthly rent payment',
                ]);
            }

            // Deposit transaction for all
            Transaction::create([
                'lease_id'       => $lease->id,
                'type'           => 'deposit',
                'amount'         => $lease->deposit_amount,
                'reference_code' => 'TXN-' . strtoupper(Str::random(6)),
                'payment_method' => 'bank_transfer',
                'paid_at'        => $lease->start_date,
                'notes'          => 'Security deposit',
            ]);
        }
    }
}