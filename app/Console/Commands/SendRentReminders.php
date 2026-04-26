<?php

namespace App\Console\Commands;

use App\Models\Lease;
use App\Models\Property;
use App\Models\Unit;
use App\Services\SmsService;
use Illuminate\Console\Command;

class SendRentReminders extends Command
{
    protected $signature   = 'reminders:send-rent';
    protected $description = 'Send SMS rent reminders to all tenants with unpaid rent this month';

    public function __construct(protected SmsService $smsService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Sending rent reminders...');

        $sent   = 0;
        $failed = 0;

        // Get all active leases
        $leases = Lease::where('status', 'active')
            ->with(['tenant', 'unit.property'])
            ->get();

        foreach ($leases as $lease) {
            // Skip if already paid this month
            $paid = $lease->transactions()
                ->where('type', 'rent')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->exists();

            if ($paid) continue;

            $result = $this->smsService->sendNudge(
                $lease->tenant->phone,
                $lease->tenant->full_name,
                $lease->rent_amount
            );

            $result ? $sent++ : $failed++;
        }

        $this->info("Done. Sent: {$sent}, Failed: {$failed}");
        return Command::SUCCESS;
    }
}