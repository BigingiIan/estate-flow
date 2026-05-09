<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = ['lease_id', 'type', 'amount', 'reference_code', 'payment_method', 'paid_at', 'notes'];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'paid_at' => 'date',
        ];
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }
}
