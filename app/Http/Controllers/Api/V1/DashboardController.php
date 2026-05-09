<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Transaction;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $userId      = auth()->id();
        $propertyIds = Property::where('user_id', $userId)->pluck('id');
        $unitIds     = Unit::whereIn('property_id', $propertyIds)->pluck('id');
        $leaseIds    = Lease::whereIn('unit_id', $unitIds)->where('status', 'active')->pluck('id');

        $totalUnits    = $unitIds->count();
        $occupiedUnits = Unit::whereIn('id', $unitIds)->where('status', 'occupied')->count();

        $monthlyCollected = Transaction::whereIn('lease_id', $leaseIds)
            ->where('type', 'rent')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $activeLeases = Lease::whereIn('unit_id', $unitIds)
            ->where('status', 'active')
            ->with(['tenant:id,full_name,phone', 'unit:id,unit_number'])
            ->get();

        $arrears = $activeLeases->filter(function ($lease) {
            return !$lease->transactions()
                ->where('type', 'rent')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->exists();
        })->map(fn($l) => [
            'tenant'     => $l->tenant->full_name,
            'unit'       => $l->unit->unit_number,
            'amount_due' => $l->rent_amount,
        ])->values();

        return response()->json([
            'summary' => [
                'total_properties'   => $propertyIds->count(),
                'total_units'        => $totalUnits,
                'occupied_units'     => $occupiedUnits,
                'vacant_units'       => $totalUnits - $occupiedUnits,
                'occupancy_rate'     => $totalUnits > 0
                    ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0,
                'monthly_collected'  => (float) $monthlyCollected,
                'arrears_count'      => $arrears->count(),
            ],
            'arrears'    => $arrears,
            'generated'  => now()->toISOString(),
        ]);
    }
}