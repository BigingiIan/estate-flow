<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\Transaction;
use Illuminate\Support\Str;

class TransactionService
{
    /**
     * Record a payment transaction against a lease.
     */
    public function recordPayment(Lease $lease, array $data): Transaction
    {
        if ($lease->status !== 'active') {
            throw new \Exception("Cannot record a payment against an inactive lease.");
        }

        return Transaction::create([
            'lease_id'        => $lease->id,
            'type'            => $data['type'],
            'amount'          => $data['amount'],
            'reference_code'  => $this->generateReference(),
            'payment_method'  => $data['payment_method'] ?? null,
            'paid_at'         => $data['paid_at'] ?? now(),
            'notes'           => $data['notes'] ?? null,
        ]);
    }

    /**
     * Generate a unique human-readable reference code.
     * Format: TXN-XXXXXX (e.g. TXN-A3F9K2)
     */
    private function generateReference(): string
    {
        do {
            $reference = 'TXN-' . strtoupper(Str::random(6));
        } while (Transaction::where('reference_code', $reference)->exists());

        return $reference;
    }

    /**
     * Get total amount paid for a lease.
     */
    public function totalPaid(Lease $lease): float
    {
        return $lease->transactions()
            ->whereIn('type', ['rent', 'deposit'])
            ->sum('amount');
    }

    /**
     * Get total rent collected for the authenticated user this month.
     */
    public function monthlyCollected(): float
    {
        return Transaction::whereHas('lease.unit.property', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->where('type', 'rent')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');
    }
}