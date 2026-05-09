<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Unit;
use App\Services\UnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct(protected UnitService $unitService) {}

    public function index(Request $request): JsonResponse
    {
        $propertyIds = Property::pluck('id');

        $units = Unit::whereIn('property_id', $propertyIds)
            ->with(['property:id,name', 'leases' => fn($q) =>
                $q->where('status', 'active')->with('tenant:id,full_name')])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->property_id, fn($q) => $q->where('property_id', $request->property_id))
            ->paginate(20);

        return response()->json($units);
    }

    public function byProperty(Property $property): JsonResponse
    {
        $units = $property->units()
            ->with(['leases' => fn($q) => $q->where('status', 'active')->with('tenant')])
            ->get();

        return response()->json(['data' => $units]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'unit_number' => 'required|string|max:20',
            'base_rent'   => 'required|numeric|min:0',
            'bedrooms'    => 'required|integer|min:1',
            'bathrooms'   => 'required|integer|min:1',
            'status'      => 'nullable|in:vacant,occupied,maintenance',
        ]);

        $property = Property::findOrFail($validated['property_id']);

        try {
            $unit = $this->unitService->create($property, $validated);
            return response()->json(['data' => $unit, 'message' => 'Unit created.'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Unit $unit): JsonResponse
    {
        $unit->load(['property', 'leases.tenant', 'leases.transactions']);
        return response()->json(['data' => $unit]);
    }

    public function update(Request $request, Unit $unit): JsonResponse
    {
        $validated = $request->validate([
            'unit_number' => 'sometimes|string|max:20',
            'base_rent'   => 'sometimes|numeric|min:0',
            'bedrooms'    => 'sometimes|integer|min:1',
            'bathrooms'   => 'sometimes|integer|min:1',
        ]);

        $this->unitService->update($unit, $validated);

        return response()->json(['data' => $unit->fresh(), 'message' => 'Unit updated.']);
    }

    public function destroy(Unit $unit): JsonResponse
    {
        try {
            $this->unitService->delete($unit);
            return response()->json(['message' => 'Unit deleted.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}