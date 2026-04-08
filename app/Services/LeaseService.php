<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class LeaseService
{
    /**
     * Create a new lease and mark the unit as occupied.
     */
    public function createLease(array $data): Lease
    {
        return DB::transaction(function () use ($data) {
            // Ensure unit is available before creating lease
            $unit = Unit::findOrFail($data['unit_id']);

            if ($unit->status !== 'vacant') {
                throw new \Exception("Unit {$unit->unit_number} is not available for lease.");
            }

            // Create the lease
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

            // Flip unit status to occupied
            $unit->update(['status' => 'occupied']);

            return $lease;
        });
    }

    /**
     * Terminate a lease and mark the unit as vacant.
     */
    public function terminateLease(Lease $lease): Lease
    {
        return DB::transaction(function () use ($lease) {
            if ($lease->status !== 'active') {
                throw new \Exception("Only active leases can be terminated.");
            }

            $lease->update(['status' => 'terminated']);

            // Flip unit back to vacant
            $lease->unit->update(['status' => 'vacant']);

            return $lease;
        });
    }

    /**
     * Mark a lease as expired (used for scheduled checks).
     */
    public function expireLease(Lease $lease): Lease
    {
        return DB::transaction(function () use ($lease) {
            $lease->update(['status' => 'expired']);

            $lease->unit->update(['status' => 'vacant']);

            return $lease;
        });
    }

    /**
     * Check and expire all leases whose end_date has passed.
     */
    public function expireOverdueLeases(): void
    {
        Lease::where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<', now()->toDateString())
            ->each(function (Lease $lease) {
                $this->expireLease($lease);
            });
    }
}