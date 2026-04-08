<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\Property;
use App\Models\Transaction;
use App\Models\Unit;

class DashboardService
{
    /**
     * Get all summary stats for the authenticated landlord's dashboard.
     */
    public function getSummary(): array
    {
        $userId = auth()->id();

        $propertyIds = Property::where('user_id', $userId)->pluck('id');
        $unitIds = Unit::whereIn('property_id', $propertyIds)->pluck('id');

        return [
            'total_properties'  => $propertyIds->count(),
            'total_units'       => $unitIds->count(),
            'vacant_units'      => Unit::whereIn('id', $unitIds)
                                    ->where('status', 'vacant')->count(),
            'occupied_units'    => Unit::whereIn('id', $unitIds)
                                    ->where('status', 'occupied')->count(),
            'active_leases'     => Lease::whereIn('unit_id', $unitIds)
                                    ->where('status', 'active')->count(),
            'monthly_collected' => Transaction::whereIn('lease_id',
                                    Lease::whereIn('unit_id', $unitIds)->pluck('id'))
                                    ->where('type', 'rent')
                                    ->whereMonth('paid_at', now()->month)
                                    ->whereYear('paid_at', now()->year)
                                    ->sum('amount'),
        ];
    }
}