<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LeaseService;
use App\Services\TransactionService;
use App\Services\DashboardService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LeaseService::class);
        $this->app->singleton(TransactionService::class);
        $this->app->singleton(DashboardService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
