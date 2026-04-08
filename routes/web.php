<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
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