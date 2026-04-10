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
    Volt::route('/properties/{property}/edit', 'pages/properties/edit')->name('properties.edit');
    
    Route::delete('/properties/{property}', function (\App\Models\Property $property) {
        $service = app(\App\Services\PropertyService::class);
        try {
            $service->delete($property);
            return redirect()->route('properties.index')->with('success', 'Property deleted.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    })->name('properties.destroy');
    
    Volt::route('/units', 'pages/units/index')->name('units.index');
    Volt::route('/units/{unit}/edit', 'pages/units/edit')->name('units.edit');
    
    Route::delete('/units/{unit}', function (\App\Models\Unit $unit) {
        $service = app(\App\Services\UnitService::class);
        try {
            $property = $unit->property;
            $service->delete($unit);
            return redirect()->route('properties.show', $property)->with('success', 'Unit deleted.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    })->name('units.destroy');
    
    Volt::route('/tenants', 'pages/tenants/index')->name('tenants.index');
    Volt::route('/tenants/create', 'pages/tenants/create')->name('tenants.create');
    Volt::route('/tenants/{tenant}/edit', 'pages/tenants/edit')->name('tenants.edit');
    Volt::route('/tenants/{tenant}', 'pages/tenants/show')->name('tenants.show');
    
    Route::delete('/tenants/{tenant}', function (\App\Models\Tenant $tenant) {
        $service = app(\App\Services\TenantService::class);
        try {
            $service->delete($tenant);
            return redirect()->route('tenants.index')->with('success', 'Tenant deleted.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    })->name('tenants.destroy');
    
    Volt::route('/leases', 'pages/leases/index')->name('leases.index');
    Volt::route('/leases/create', 'pages/leases/create')->name('leases.create');
    Volt::route('/leases/{lease}/renew', 'pages/leases/renew')->name('leases.renew');
    
    Volt::route('/transactions', 'pages/transactions/index')->name('transactions.index');
    Volt::route('/transactions/create', 'pages/transactions/create')->name('transactions.create');
    Volt::route('/transactions/{transaction}/receipt', 'pages/transactions/receipt')->name('transactions.receipt');
    
    Volt::route('/reports', 'pages/reports/index')->name('reports.index');
    Volt::route('/profile', 'pages/profile/index')->name('profile');
    Volt::route('/properties/{property}/units/create', 'pages/units/create')->name('units.create');
    
    Route::get('/reports/print', function () {
        // handled in the reports page itself
    })->name('reports.print');

    Route::post('/leases/{lease}/terminate', function (\App\Models\Lease $lease) {
        $service = app(\App\Services\LeaseService::class);
        $service->terminateLease($lease);
        return back()->with('success', 'Lease terminated successfully.');
    })->name('leases.terminate');

    Route::post('/tenants/{tenant}/nudge', function (\App\Models\Tenant $tenant) {

        $service = app(\App\Services\SmsService::class);

        $lease = $tenant->leases()->where('status', 'active')->first();
        $amount = $lease ? $lease->rent_amount : 0;
        
        $sent = $service->sendNudge($tenant->phone, $tenant->full_name, $amount);

        return back()->with(
            $sent ? 'success' : 'error',
            $sent ? 'Nudge sent to ' . $tenant->full_name : 'Failed to send nudge.'
        );
    })->name('tenants.nudge');

});

require __DIR__.'/auth.php';