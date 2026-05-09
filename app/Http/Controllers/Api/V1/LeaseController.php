<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Unit;
use App\Services\LeaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaseController extends Controller
{
    public function __construct(protected LeaseService $leaseService) {}

    public function index(): JsonResponse
    {
        $propertyIds = Property::pluck('id');
        $unitIds     = Unit::whereIn('property_id', $propertyIds)->pluck('id');

        $leases = Lease::whereIn('unit_id', $unitIds)
            ->with(['tenant:id,full_name,phone', 'unit:id,unit_number,property_id', 'unit.property:id,name'])
            ->latest()->paginate(20);

        return response()->json($leases);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unit_id'        => 'required|exists:units,id',
            'tenant_id'      => 'required|exists:tenants,id',
            'start_date'     => 'required|date',
            'end_date'       => 'nullable|date|after:start_date',
            'rent_amount'    => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string',
        ]);

        try {
            $lease = $this->leaseService->createLease($validated);
            return response()->json(['data' => $lease, 'message' => 'Lease created.'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Lease $lease): JsonResponse
    {
        $lease->load(['tenant', 'unit.property', 'transactions']);
        return response()->json(['data' => $lease]);
    }

    public function update(Request $request, Lease $lease): JsonResponse
    {
        $validated = $request->validate([
            'end_date'    => 'nullable|date',
            'rent_amount' => 'sometimes|numeric|min:0',
            'notes'       => 'nullable|string',
        ]);

        $lease->update($validated);

        return response()->json(['data' => $lease->fresh(), 'message' => 'Lease updated.']);
    }

    public function destroy(Lease $lease): JsonResponse
    {
        return response()->json(['message' => 'Use the terminate endpoint instead.'], 405);
    }

    public function terminate(Lease $lease): JsonResponse
    {
        try {
            $this->leaseService->terminateLease($lease);
            return response()->json(['message' => 'Lease terminated. Unit marked vacant.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function renew(Request $request, Lease $lease): JsonResponse
    {
        $validated = $request->validate([
            'end_date'    => 'required|date|after:today',
            'rent_amount' => 'sometimes|numeric|min:0',
        ]);

        $lease->update([
            'end_date'    => $validated['end_date'],
            'rent_amount' => $validated['rent_amount'] ?? $lease->rent_amount,
            'status'      => 'active',
        ]);

        return response()->json(['data' => $lease->fresh(), 'message' => 'Lease renewed.']);
    }
}