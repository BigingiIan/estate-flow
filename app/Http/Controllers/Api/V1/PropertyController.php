<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\PropertyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function __construct(protected PropertyService $propertyService) {}

    public function index(): JsonResponse
    {
        $properties = Property::withCount('units')
            ->with(['units' => fn($q) => $q->where('status', 'vacant')])
            ->latest()->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'location'    => $p->location,
                'description' => $p->description,
                'type'        => $p->type,
                'total_units' => $p->units_count,
                'vacant_units'=> $p->units->count(),
                'created_at'  => $p->created_at->toDateString(),
            ]);

        return response()->json(['data' => $properties]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'location'    => 'required|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'nullable|in:residential,commercial,mixed',
        ]);

        $property = $this->propertyService->create($validated);

        return response()->json(['data' => $property, 'message' => 'Property created.'], 201);
    }

    public function show(Property $property): JsonResponse
    {
        $property->load('units.leases.tenant');
        return response()->json(['data' => $property]);
    }

    public function update(Request $request, Property $property): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'location'    => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $this->propertyService->update($property, $validated);

        return response()->json(['data' => $property->fresh(), 'message' => 'Property updated.']);
    }

    public function destroy(Property $property): JsonResponse
    {
        try {
            $this->propertyService->delete($property);
            return response()->json(['message' => 'Property deleted.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}