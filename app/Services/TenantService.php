<?php

namespace App\Services;

use App\Models\Tenant;

class TenantService
{
    public function create(array $data): Tenant
    {
        return Tenant::create([
            'full_name'         => $data['full_name'],
            'email'             => $data['email'] ?? null,
            'phone'             => $data['phone'],
            'id_type'           => $data['id_type'],
            'id_number'         => $data['id_number'],
            'emergency_contact' => $data['emergency_contact'] ?? null,
        ]);
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update([
            'full_name'         => $data['full_name'],
            'email'             => $data['email'] ?? null,
            'phone'             => $data['phone'],
            'id_type'           => $data['id_type'],
            'id_number'         => $data['id_number'],
            'emergency_contact' => $data['emergency_contact'] ?? null,
        ]);

        return $tenant;
    }

    public function getLeaseHistory(Tenant $tenant): \Illuminate\Support\Collection
    {
        return $tenant->leases()
            ->with(['unit.property', 'transactions'])
            ->orderByDesc('start_date')
            ->get();
    }

    public function hasActiveLease(Tenant $tenant): bool
    {
        return $tenant->leases()
            ->where('status', 'active')
            ->exists();
    }
}