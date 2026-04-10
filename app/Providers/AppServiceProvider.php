<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SmsService;
use App\Services\DashboardService;
use App\Services\LeaseService;
use App\Services\TransactionService;
use App\Services\PropertyService;
use App\Services\UnitService;
use App\Services\TenantService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsService::class);
        $this->app->singleton(DashboardService::class);
        $this->app->singleton(LeaseService::class);
        $this->app->singleton(TransactionService::class);
        $this->app->singleton(PropertyService::class);
        $this->app->singleton(UnitService::class);
        $this->app->singleton(TenantService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
