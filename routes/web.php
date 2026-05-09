<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Schedule;

Schedule::command('leases:expire-overdue')->dailyAt('00:00');
Schedule::command('reminders:send-rent')->monthlyOn(3, '09:00');

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        $userId      = auth()->id();
        $propertyIds = \App\Models\Property::where('user_id', $userId)->pluck('id');
        $unitIds     = \App\Models\Unit::whereIn('property_id', $propertyIds)->pluck('id');
        $leaseIds    = \App\Models\Lease::whereIn('unit_id', $unitIds)
                        ->where('status', 'active')->pluck('id');

        $totalUnits    = $unitIds->count();
        $occupiedUnits = \App\Models\Unit::whereIn('id', $unitIds)->where('status', 'occupied')->count();
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0;

        $monthlyCollected = \App\Models\Transaction::whereIn('lease_id', $leaseIds)
            ->where('type', 'rent')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $pendingArrears = \App\Models\Lease::whereIn('unit_id', $unitIds)
            ->where('status', 'active')
            ->get()
            ->sum(function ($lease) {
                $paid = $lease->transactions()
                    ->where('type', 'rent')
                    ->whereMonth('paid_at', now()->month)
                    ->whereYear('paid_at', now()->year)
                    ->sum('amount');
                return $paid < $lease->rent_amount ? $lease->rent_amount - $paid : 0;
            });

        // Vacancy cost tracker — daily lost revenue from vacant units
        $vacantUnits = \App\Models\Unit::whereIn('property_id', $propertyIds)
            ->where('status', 'vacant')->get();
        $vacancyCost = $vacantUnits->sum(fn($u) => $u->base_rent / 30);
        $vacantCount = $vacantUnits->count();

        // Priority arrears with reliability score
        $priorityArrears = \App\Models\Lease::whereIn('unit_id', $unitIds)
            ->where('status', 'active')
            ->with(['tenant', 'unit'])
            ->get()
            ->filter(function ($lease) {
                return !$lease->transactions()
                    ->where('type', 'rent')
                    ->whereMonth('paid_at', now()->month)
                    ->whereYear('paid_at', now()->year)
                    ->exists();
            })
            ->map(function ($lease) {
                // Reliability score: 100 - (late payments / total months * 100)
                $totalMonths = max(1, now()->diffInMonths($lease->start_date));
                $lateMonths  = 0;
                for ($i = 0; $i < min($totalMonths, 6); $i++) {
                    $date = now()->subMonths($i);
                    $paid = $lease->transactions()
                        ->where('type', 'rent')
                        ->whereMonth('paid_at', $date->month)
                        ->whereYear('paid_at', $date->year)
                        ->exists();
                    if (!$paid) $lateMonths++;
                }
                $score = max(0, 100 - round(($lateMonths / min($totalMonths, 6)) * 100));

                return [
                    'name'              => $lease->tenant->full_name,
                    'unit'              => $lease->unit->unit_number,
                    'phone'             => $lease->tenant->phone,
                    'amount'            => $lease->rent_amount,
                    'days_overdue'      => (int) max(0, now()->diffInDays($lease->start_date)),
                    'reliability_score' => $score,
                ];
            })
            ->sortByDesc('days_overdue')
            ->take(5)
            ->values();

        // Recent transactions
        $recentTransactions = \App\Models\Transaction::whereIn('lease_id', $leaseIds)
            ->with(['lease.tenant'])
            ->latest('paid_at')
            ->take(5)
            ->get()
            ->map(fn($t) => [
                'tenant'    => $t->lease->tenant->full_name,
                'reference' => $t->reference_code,
                'method'    => ucfirst(str_replace('_', ' ', $t->payment_method ?? 'cash')),
                'amount'    => $t->amount,
                'date'      => \Carbon\Carbon::parse($t->paid_at)->format('d M'),
            ]);

        return view('dashboard', compact(
            'monthlyCollected', 'pendingArrears', 'occupancyRate',
            'priorityArrears', 'totalUnits', 'occupiedUnits',
            'vacancyCost', 'vacantCount', 'recentTransactions'
        ));
    })->name('dashboard');

    Volt::route('/settings', 'pages/settings/index')->name('settings');

    Volt::route('/properties', 'pages/properties/index')->name('properties.index');
    Volt::route('/properties/create', 'pages/properties/create')->name('properties.create');
    Volt::route('/properties/{property}', 'pages/properties/show')->name('properties.show');
    Volt::route('/properties/{property}/edit', 'pages/properties/edit')->name('properties.edit');
    Volt::route('/properties/{property}/units/batch', 'pages/units/batch')->name('units.batch');
    
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
    Volt::route('/leases/batch', 'pages/leases/batch')->name('leases.batch');
    
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

    Route::post('/properties/{property}/mark-all-paid', function (\App\Models\Property $property) {
        $service  = app(\App\Services\TransactionService::class);
        $unitIds  = $property->units()->pluck('id');
        $leases   = \App\Models\Lease::whereIn('unit_id', $unitIds)
                        ->where('status', 'active')
                        ->get();

        $created = 0;
        foreach ($leases as $lease) {
            $alreadyPaid = $lease->transactions()
                ->where('type', 'rent')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
            ->exists();

            if (!$alreadyPaid) {
                $service->recordPayment($lease, [
                    'type'           => 'rent',
                    'amount'         => $lease->rent_amount,
                    'payment_method' => 'cash',
                    'paid_at'        => now()->toDateString(),
                    'notes'          => 'Bulk payment — ' . now()->format('F Y'),
                ]);
                $created++;
            }
        }

        return back()->with('success',
            $created > 0
                ? "{$created} rent payments recorded for " . now()->format('F Y') . "."
                : "All tenants in this property are already paid for " . now()->format('F Y') . "."
        );
    })->name('properties.mark-all-paid');

    Route::get('/transactions/export', function () {
        $propertyIds = \App\Models\Property::pluck('id');
        $unitIds     = \App\Models\Unit::whereIn('property_id', $propertyIds)->pluck('id');
        $leaseIds    = \App\Models\Lease::whereIn('unit_id', $unitIds)->pluck('id');

        $transactions = \App\Models\Transaction::whereIn('lease_id', $leaseIds)
            ->with(['lease.tenant', 'lease.unit.property'])
            ->orderByDesc('paid_at')
            ->get();

        $filename = 'transactions-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            // CSV header row
            fputcsv($handle, [
                'Reference', 'Date', 'Tenant', 'Property',
                'Unit', 'Type', 'Amount (KES)', 'Method', 'Notes'
            ]);

            foreach ($transactions as $txn) {
                fputcsv($handle, [
                    $txn->reference_code,
                    $txn->paid_at ? \Carbon\Carbon::parse($txn->paid_at)->format('d M Y') : '',
                    $txn->lease->tenant->full_name,
                    $txn->lease->unit->property->name,
                    $txn->lease->unit->unit_number,
                    ucfirst($txn->type),
                    $txn->amount,
                    $txn->payment_method ? ucfirst(str_replace('_', ' ', $txn->payment_method)) : '',
                    $txn->notes ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    })->name('transactions.export');

});

require __DIR__.'/auth.php';
