<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class LeaseService
{
    public function createLease(array $data): Lease
    {
        return DB::transaction(function () use ($data) {
            $unit = Unit::findOrFail($data['unit_id']);

            if ($unit->status !== 'vacant') {
                throw new \Exception("Unit {$unit->unit_number} is not available for lease.");
            }

            $lease = Lease::create([
                'unit_id'        => $data['unit_id'],
                'tenant_id'      => $data['tenant_id'],
                'start_date'     => $data['start_date'],
                'end_date'       => $data['end_date'] ?? null,
                'rent_amount'    => $data['rent_amount'],
                'deposit_amount' => $data['deposit_amount'] ?? 0,
                'deposit_status' => 'pending',
                'status'         => 'active',
                'notes'          => $data['notes'] ?? null,
            ]);

            $unit->update(['status' => 'occupied']);

            return $lease;
        });
    }

    public function terminateLease(Lease $lease): Lease
    {
        return DB::transaction(function () use ($lease) {
            if ($lease->status !== 'active') {
                throw new \Exception("Only active leases can be terminated.");
            }

            $lease->update(['status' => 'terminated']);
            $lease->unit->update(['status' => 'vacant']);

            return $lease;
        });
    }

    public function expireLease(Lease $lease): Lease
    {
        return DB::transaction(function () use ($lease) {
            $lease->update(['status' => 'expired']);
            $lease->unit->update(['status' => 'vacant']);
            return $lease;
        });
    }

    public function expireOverdueLeases(): void
    {
        Lease::where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<', now()->toDateString())
            ->each(fn(Lease $lease) => $this->expireLease($lease));
    }
}