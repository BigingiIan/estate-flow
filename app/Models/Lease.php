<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lease extends Model
{
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    protected $fillable = ['unit_id', 'tenant_id', 'start_date', 'end_date', 'rent_amount', 'deposit_amount', 'deposit_status', 'status', 'notes'];
}
