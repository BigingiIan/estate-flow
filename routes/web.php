<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        $service = app(\App\Services\DashboardService::class);
        $summary = $service->getSummary();

        $userId = auth()->id();
        $propertyIds = \App\Models\Property::where('user_id', $userId)->pluck('id');
        $unitIds = \App\Models\Unit::whereIn('property_id', $propertyIds)->pluck('id');

        $priorityArrears = \App\Models\Lease::whereIn('unit_id', $unitIds)
            ->where('status', 'active')
            ->with(['tenant', 'unit'])
            ->get()
            ->map(function ($lease) {
                return [
                    'name'         => $lease->tenant->full_name,
                    'unit'         => $lease->unit->unit_number,
                    'phone'        => $lease->tenant->phone,
                    'amount'       => $lease->rent_amount,
                    'days_overdue' => (int) max(0, now()->diffInDays($lease->start_date)),
                ];
            })
            ->sortByDesc('days_overdue')
            ->take(5)
            ->values();

        return view('dashboard', [
            'monthlyCollected' => $summary['monthly_collected'],
            'pendingArrears'   => $summary['monthly_collected'] > 0
                ? max(0, \App\Models\Lease::whereIn('unit_id', $unitIds)
                    ->where('status', 'active')
                    ->sum('rent_amount') - $summary['monthly_collected'])
                : 0,
            'occupancyRate'    => $summary['total_units'] > 0
                ? round(($summary['occupied_units'] / $summary['total_units']) * 100)
                : 0,
            'priorityArrears'  => $priorityArrears,
        ]);
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