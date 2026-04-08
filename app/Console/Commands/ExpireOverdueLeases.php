<?php

namespace App\Console\Commands;

use App\Services\LeaseService;
use Illuminate\Console\Command;

class ExpireOverdueLeases extends Command
{
    protected $signature = 'leases:expire-overdue';

    protected $description = 'Mark all leases past their end date as expired and free up their units';

    public function __construct(protected LeaseService $leaseService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking for overdue leases...');

        $this->leaseService->expireOverdueLeases();

        $this->info('Done. All overdue leases have been expired.');

        return Command::SUCCESS;
    }
}