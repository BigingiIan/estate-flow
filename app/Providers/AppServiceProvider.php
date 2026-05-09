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
use App\Services\CurrencyService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsService::class);
        $this->app->singleton(DashboardService::class);
        $this->app->singleton(LeaseService::class);
        $this->app->singleton(TransactionService::class);
        $this->app->singleton(PropertyService::class);
        $this->app->singleton(UnitService::class);
        $this->app->singleton(TenantService::class);
        $this->app->singleton(CurrencyService::class);
    }

    public function boot(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });
        View::composer('*', function($view){
            $view->with('currencyService', app(CurrencyService::class));
        });

        Blade::directive('money', function($expression){
            return "<?php echo app(\App\Services\CurrencyService::class)->format($expression); ?>";
        });

        Blade::directive('symbol', function(){
            return "<?php echo app(\App\Services\CurrencyService::class)->getSymbol(); ?>";
        });

        Blade::directive('appdate', function($expression){
            return "<?php echo \Carbon\Carbon::parse($expression)->format(session('estateflow_prefs.date_format','d M Y')); ?>";
        });
    }
}
