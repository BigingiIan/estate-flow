<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Tenant extends Model
{
    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function units(): HasManyThrough
    {
        return $this->hasManyThrough(Unit::class, Lease::class, 'tenant_id', 'id', 'id', 'unit_id');
    }

    protected $fillable = ['full_name', 'email', 'phone', 'id_type', 'id_number', 'emergency_contact', 'emergency_contact_phone'];
}
