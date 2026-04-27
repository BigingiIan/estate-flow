<?php

namespace Database\Seeders;

use App\Models\Lease;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $today = now()->startOfDay();
        $currentMonth = $today->copy()->startOfMonth();
        $previousMonth = $today->copy()->subMonthNoOverflow()->startOfMonth();

        foreach (Lease::with(['tenant', 'unit.property'])->get() as $lease) {
            $code = str($lease->unit->property->name)->upper()->replace(' ', '')->substr(0, 4)->value();

            $this->createTransaction(
                $lease,
                'deposit',
                $lease->deposit_amount,
                "{$code}-DEP-{$lease->id}-001",
                'bank_transfer',
                Carbon::parse($lease->start_date)->setTime(10, 0),
                'Security deposit received at move-in.'
            );

            if ($lease->deposit_status === 'pending') {
                $this->createTransaction(
                    $lease,
                    'deposit',
                    round($lease->deposit_amount / 2, 2),
                    "{$code}-DEP-{$lease->id}-002",
                    'mpesa',
                    Carbon::parse($lease->start_date)->addDays(14)->setTime(9, 30),
                    'First installment toward security deposit.'
                );
            }

            if ($lease->status === 'active') {
                $this->createTransaction(
                    $lease,
                    'rent',
                    $lease->rent_amount,
                    "{$code}-RNT-{$lease->id}-001",
                    'mpesa',
                    $previousMonth->copy()->addDays(($lease->id % 5) + 1)->setTime(8, 45),
                    'Previous month rent payment.'
                );

                if (! in_array($lease->tenant->full_name, ['John Ndegwa', 'Moses Kamau'], true)) {
                    $method = in_array($lease->tenant->full_name, ['Peter Mwangi', 'Robert Ochieng'], true)
                        ? 'bank_transfer'
                        : 'mpesa';

                    $this->createTransaction(
                        $lease,
                        'rent',
                        $lease->rent_amount,
                        "{$code}-RNT-{$lease->id}-002",
                        $method,
                        $currentMonth->copy()->addDays(($lease->id % 4) + 2)->setTime(11, 15),
                        'Current month rent payment.'
                    );
                }

                if ($lease->tenant->full_name === 'Sarah Otieno') {
                    $this->createTransaction(
                        $lease,
                        'penalty',
                        2500,
                        "{$code}-PEN-{$lease->id}-001",
                        'cash',
                        $currentMonth->copy()->addDays(8)->setTime(15, 0),
                        'Late payment penalty for delayed settlement.'
                    );
                }
            }

            if ($lease->status === 'expired') {
                $this->createTransaction(
                    $lease,
                    'rent',
                    $lease->rent_amount,
                    "{$code}-RNT-{$lease->id}-099",
                    'bank_transfer',
                    Carbon::parse($lease->end_date)->subMonth()->setTime(14, 30),
                    'Final rent payment before expiry.'
                );

                $this->createTransaction(
                    $lease,
                    'refund',
                    $lease->deposit_amount,
                    "{$code}-REF-{$lease->id}-001",
                    'bank_transfer',
                    Carbon::parse($lease->end_date)->addDays(7)->setTime(10, 15),
                    'Security deposit refunded after checkout inspection.'
                );
            }

            if ($lease->status === 'terminated') {
                $this->createTransaction(
                    $lease,
                    'penalty',
                    10000,
                    "{$code}-PEN-{$lease->id}-002",
                    'cash',
                    Carbon::parse($lease->end_date)->subDays(3)->setTime(16, 20),
                    'Early termination fee.'
                );

                $this->createTransaction(
                    $lease,
                    'refund',
                    round($lease->deposit_amount * 0.6, 2),
                    "{$code}-REF-{$lease->id}-002",
                    'bank_transfer',
                    Carbon::parse($lease->end_date)->addDays(10)->setTime(9, 0),
                    'Partial deposit refund after repairs deduction.'
                );
            }
        }
    }

    private function createTransaction(
        Lease $lease,
        string $type,
        float $amount,
        string $referenceCode,
        string $paymentMethod,
        Carbon $paidAt,
        string $notes
    ): void {
        Transaction::create([
            'lease_id' => $lease->id,
            'type' => $type,
            'amount' => $amount,
            'reference_code' => $referenceCode,
            'payment_method' => $paymentMethod,
            'paid_at' => $paidAt,
            'notes' => $notes,
        ]);
    }
}
