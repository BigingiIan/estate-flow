<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Unit;
use App\Models\Lease;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    public function getSummary(): array
    {
        $userId = Auth::id();
        $propertyIds = Property::where('user_id', $userId)->pluck('id');
        $unitIds = Unit::whereIn('property_id', $propertyIds)->pluck('id');
        $leaseIds = Lease::whereIn('unit_id', $unitIds)
            ->where('status', 'active')
            ->pluck('id');

        $totalUnits = $unitIds->count();
        $occupiedUnits = Unit::whereIn('id', $unitIds)
            ->where('status', 'occupied')
            ->count();

        $monthlyCollected = Transaction::whereIn('lease_id', $leaseIds)
            ->where('type', 'rent')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        return [
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'monthly_collected' => $monthlyCollected,
        ];
    }
}