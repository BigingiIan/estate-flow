<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        $userId = auth()->id();
        $propertyIds = \App\Models\Property::where('user_id', $userId)->pluck('id');
        $unitIds = \App\Models\Unit::whereIn('property_id', $propertyIds)->pluck('id');
        $leaseIds = \App\Models\Lease::whereIn('unit_id', $unitIds)
                        ->where('status', 'active')->pluck('id');

        $totalUnits = $unitIds->count();
        $occupiedUnits = \App\Models\Unit::whereIn('id', $unitIds)
                            ->where('status', 'occupied')->count();
        $occupancyRate = $totalUnits > 0
                            ? round(($occupiedUnits / $totalUnits) * 100)
                            : 0;

        $monthlyCollected = \App\Models\Transaction::whereIn('lease_id', $leaseIds)
                                ->where('type', 'rent')
                                ->whereMonth('paid_at', now()->month)
                                ->whereYear('paid_at', now()->year)
                                ->sum('amount');

        $pendingArrears = \App\Models\Transaction::whereIn('lease_id', $leaseIds)
                                ->where('type', 'rent')
                                ->whereNull('paid_at')
                                ->sum('amount');

        $priorityArrears = \App\Models\Lease::whereIn('unit_id', $unitIds)
                                ->where('status', 'active')
                                ->where('start_date', '<', now())
                                ->with(['tenant', 'unit'])
                                ->get()
                                ->map(function ($lease) {
                                    $daysOverdue = now()->diffInDays($lease->start_date, false) * -1;
                                    return [
                                        'name'         => $lease->tenant->full_name,
                                        'unit'         => $lease->unit->unit_number,
                                        'phone'        => $lease->tenant->phone,
                                        'amount'       => $lease->rent_amount,
                                        'days_overdue' => max(0, $daysOverdue),
                                    ];
                                })
                                ->sortByDesc('days_overdue')
                                ->take(5)
                                ->values();

        return view('dashboard', compact(
            'monthlyCollected',
            'pendingArrears',
            'occupancyRate',
            'priorityArrears'
        ));
    })->name('dashboard');

    Volt::route('/properties', 'pages/properties/index')->name('properties.index');
    Volt::route('/properties/create', 'pages/properties/create')->name('properties.create');
    Volt::route('/properties/{property}', 'pages/properties/show')->name('properties.show');
    Volt::route('/units', 'pages/units/index')->name('units.index');
    Volt::route('/tenants', 'pages/tenants/index')->name('tenants.index');
    Volt::route('/leases', 'pages/leases/index')->name('leases.index');
    Volt::route('/transactions', 'pages/transactions/index')->name('transactions.index');

});

require __DIR__.'/auth.php';