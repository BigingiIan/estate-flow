<?php

use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\LeaseController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Middleware\RequireApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public auth endpoints
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/login', function (Request $request) {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!\Illuminate\Support\Facades\Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user  = \App\Models\User::where('email', $request->email)->first();
        $token = $user->createToken('estateflow-api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role'  => $user->role,
            ],
        ]);
    });

    Route::post('auth/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    })->middleware('auth:sanctum');
});

// Protected API routes
Route::prefix('v1')->name('api.v1.')->middleware(['auth:sanctum', RequireApiToken::class])->group(function () {

    // Dashboard summary
    Route::get('dashboard', [DashboardController::class, 'index']);

    // Properties
    Route::apiResource('properties', PropertyController::class);
    Route::get('properties/{property}/units', [UnitController::class, 'byProperty']);

    // Units
    Route::apiResource('units', UnitController::class)->except(['index']);
    Route::get('units', [UnitController::class, 'index']);

    // Tenants
    Route::apiResource('tenants', TenantController::class);

    // Leases
    Route::apiResource('leases', LeaseController::class);
    Route::post('leases/{lease}/terminate', [LeaseController::class, 'terminate']);
    Route::post('leases/{lease}/renew', [LeaseController::class, 'renew']);

    // Transactions
    Route::apiResource('transactions', TransactionController::class)->only(['index', 'store', 'show']);
    Route::get('transactions/export', [TransactionController::class, 'export']);

    // M-Pesa Daraja callback (public — Safaricom calls this)
    Route::post('mpesa/callback', function (Request $request) {
        \Illuminate\Support\Facades\Log::info('M-Pesa callback received', $request->all());
        // TODO: parse STK push result and create transaction
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    })->withoutMiddleware('auth:sanctum');

});
