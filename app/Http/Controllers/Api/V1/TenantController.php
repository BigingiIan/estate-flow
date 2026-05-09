<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(protected TenantService $tenantService) {}

    public function index(Request $request): JsonResponse
    {
        $tenants = Tenant::with(['leases' => fn($q) => $q->where('status', 'active')->with('unit')])
            ->when($request->search, fn($q) =>
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%"))
            ->paginate(20);

        return response()->json($tenants);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name'         => 'required|string|max:255',
            'email'             => 'nullable|email|unique:tenants,email',
            'phone'             => 'required|string|max:20',
            'id_type'           => 'required|string',
            'id_number'         => 'required|string|unique:tenants,id_number',
            'emergency_contact' => 'nullable|string|max:20',
        ]);

        $tenant = $this->tenantService->create($validated);

        return response()->json(['data' => $tenant, 'message' => 'Tenant created.'], 201);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load(['leases.unit.property', 'leases.transactions']);
        return response()->json(['data' => $tenant]);
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'full_name'         => 'sometimes|string|max:255',
            'email'             => 'nullable|email',
            'phone'             => 'sometimes|string|max:20',
            'emergency_contact' => 'nullable|string|max:20',
        ]);

        $this->tenantService->update($tenant, $validated);

        return response()->json(['data' => $tenant->fresh(), 'message' => 'Tenant updated.']);
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        try {
            $this->tenantService->delete($tenant);
            return response()->json(['message' => 'Tenant deleted.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}