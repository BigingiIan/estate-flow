<?php

use App\Models\Lease;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\LeaseService;
use App\Services\TransactionService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// --- Properties ---

test('authenticated user can view properties page', function () {
    $this->get(route('properties.index'))->assertStatus(200);
});

test('property is scoped to authenticated user', function () {
    $other = User::factory()->create();

    Property::create([
        'user_id'  => $other->id,
        'name'     => 'Other Property',
        'location' => 'Elsewhere',
        'type'     => 'residential',
    ]);

    $myProperty = Property::create([
        'user_id'  => $this->user->id,
        'name'     => 'My Property',
        'location' => 'Westlands',
        'type'     => 'residential',
    ]);

    // Global scope should only return my property
    $properties = Property::all();
    expect($properties)->toHaveCount(1);
    expect($properties->first()->name)->toBe('My Property');
});

// --- Units ---

test('unit is created under a property', function () {
    $property = Property::create([
        'user_id'  => $this->user->id,
        'name'     => 'Test Property',
        'location' => 'Nairobi',
        'type'     => 'residential',
    ]);

    $unit = Unit::create([
        'property_id' => $property->id,
        'unit_number' => 'A1',
        'base_rent'   => 25000,
        'bedrooms'    => 2,
        'bathrooms'   => 1,
        'status'      => 'vacant',
    ]);

    expect($unit->property->name)->toBe('Test Property');
    expect($unit->status)->toBe('vacant');
});

test('duplicate unit number in same property is rejected', function () {
    $property = Property::create([
        'user_id'  => $this->user->id,
        'name'     => 'Test',
        'location' => 'Nairobi',
        'type'     => 'residential',
    ]);

    Unit::create([
        'property_id' => $property->id,
        'unit_number' => 'A1',
        'base_rent'   => 25000,
        'bedrooms'    => 1,
        'bathrooms'   => 1,
        'status'      => 'vacant',
    ]);

    expect(fn() => Unit::create([
        'property_id' => $property->id,
        'unit_number' => 'A1',
        'base_rent'   => 25000,
        'bedrooms'    => 1,
        'bathrooms'   => 1,
        'status'      => 'vacant',
    ]))->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);
});

// --- Lease service ---

test('creating a lease flips unit status to occupied', function () {
    $property = Property::create([
        'user_id'  => $this->user->id,
        'name'     => 'Test',
        'location' => 'Nairobi',
        'type'     => 'residential',
    ]);

    $unit = Unit::create([
        'property_id' => $property->id,
        'unit_number' => 'B1',
        'base_rent'   => 30000,
        'bedrooms'    => 1,
        'bathrooms'   => 1,
        'status'      => 'vacant',
    ]);

    $tenant = Tenant::create([
        'full_name' => 'John Doe',
        'phone'     => '+254711000001',
        'id_type'   => 'national_id',
        'id_number' => 'KE99999999',
    ]);

    $leaseService = app(LeaseService::class);
    $lease = $leaseService->createLease([
        'unit_id'     => $unit->id,
        'tenant_id'   => $tenant->id,
        'start_date'  => now()->toDateString(),
        'rent_amount' => 30000,
    ]);

    expect($lease->status)->toBe('active');
    expect($unit->fresh()->status)->toBe('occupied');
});

test('terminating a lease flips unit back to vacant', function () {
    $property = Property::create([
        'user_id'  => $this->user->id,
        'name'     => 'Test',
        'location' => 'Nairobi',
        'type'     => 'residential',
    ]);

    $unit = Unit::create([
        'property_id' => $property->id,
        'unit_number' => 'C1',
        'base_rent'   => 30000,
        'bedrooms'    => 1,
        'bathrooms'   => 1,
        'status'      => 'vacant',
    ]);

    $tenant = Tenant::create([
        'full_name' => 'Jane Doe',
        'phone'     => '+254711000002',
        'id_type'   => 'national_id',
        'id_number' => 'KE88888888',
    ]);

    $leaseService = app(LeaseService::class);

    $lease = $leaseService->createLease([
        'unit_id'     => $unit->id,
        'tenant_id'   => $tenant->id,
        'start_date'  => now()->toDateString(),
        'rent_amount' => 30000,
    ]);

    expect($unit->fresh()->status)->toBe('occupied');

    $leaseService->terminateLease($lease);

    expect($unit->fresh()->status)->toBe('vacant');
    expect($lease->fresh()->status)->toBe('terminated');
});

test('cannot lease an occupied unit', function () {
    $property = Property::create([
        'user_id'  => $this->user->id,
        'name'     => 'Test',
        'location' => 'Nairobi',
        'type'     => 'residential',
    ]);

    $unit = Unit::create([
        'property_id' => $property->id,
        'unit_number' => 'D1',
        'base_rent'   => 30000,
        'bedrooms'    => 1,
        'bathrooms'   => 1,
        'status'      => 'occupied', // already occupied
    ]);

    $tenant = Tenant::create([
        'full_name' => 'Bob Smith',
        'phone'     => '+254711000003',
        'id_type'   => 'national_id',
        'id_number' => 'KE77777777',
    ]);

    $leaseService = app(LeaseService::class);

    expect(fn() => $leaseService->createLease([
        'unit_id'     => $unit->id,
        'tenant_id'   => $tenant->id,
        'start_date'  => now()->toDateString(),
        'rent_amount' => 30000,
    ]))->toThrow(\Exception::class, 'not available for lease');
});

// --- Transaction service ---

test('recording a payment generates a unique reference code', function () {
    $property = Property::create([
        'user_id'  => $this->user->id,
        'name'     => 'Test',
        'location' => 'Nairobi',
        'type'     => 'residential',
    ]);

    $unit = Unit::create([
        'property_id' => $property->id,
        'unit_number' => 'E1',
        'base_rent'   => 30000,
        'bedrooms'    => 1,
        'bathrooms'   => 1,
        'status'      => 'vacant',
    ]);

    $tenant = Tenant::create([
        'full_name' => 'Alice K',
        'phone'     => '+254711000004',
        'id_type'   => 'national_id',
        'id_number' => 'KE66666666',
    ]);

    $lease = app(LeaseService::class)->createLease([
        'unit_id'     => $unit->id,
        'tenant_id'   => $tenant->id,
        'start_date'  => now()->toDateString(),
        'rent_amount' => 30000,
    ]);

    $txn = app(TransactionService::class)->recordPayment($lease, [
        'type'           => 'rent',
        'amount'         => 30000,
        'payment_method' => 'mpesa',
        'paid_at'        => now()->toDateString(),
    ]);

    expect($txn->reference_code)->toStartWith('TXN-');
    expect(strlen($txn->reference_code))->toBe(10); // TXN- + 6 chars
    expect($txn->amount)->toBe(30000.0);
});

test('cannot record payment against inactive lease', function () {
    $property = Property::create([
        'user_id'  => $this->user->id,
        'name'     => 'Test',
        'location' => 'Nairobi',
        'type'     => 'residential',
    ]);

    $unit = Unit::create([
        'property_id' => $property->id,
        'unit_number' => 'F1',
        'base_rent'   => 30000,
        'bedrooms'    => 1,
        'bathrooms'   => 1,
        'status'      => 'vacant',
    ]);

    $tenant = Tenant::create([
        'full_name' => 'Tom M',
        'phone'     => '+254711000005',
        'id_type'   => 'national_id',
        'id_number' => 'KE55555555',
    ]);

    $leaseService = app(LeaseService::class);
    $lease = $leaseService->createLease([
        'unit_id'     => $unit->id,
        'tenant_id'   => $tenant->id,
        'start_date'  => now()->toDateString(),
        'rent_amount' => 30000,
    ]);

    $leaseService->terminateLease($lease);

    expect(fn() => app(TransactionService::class)->recordPayment($lease->fresh(), [
        'type'           => 'rent',
        'amount'         => 30000,
        'payment_method' => 'mpesa',
        'paid_at'        => now()->toDateString(),
    ]))->toThrow(\Exception::class, 'inactive lease');
});

// --- API ---

test('api requires authentication', function () {
    $this->getJson('/api/v1/properties')->assertStatus(401);
});

test('api returns properties for authenticated user', function () {
    Property::create([
        'user_id'  => $this->user->id,
        'name'     => 'API Test Property',
        'location' => 'Nairobi',
        'type'     => 'residential',
    ]);

    $token = $this->user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/properties')
        ->assertStatus(200)
        ->assertJsonPath('data.0.name', 'API Test Property');
});