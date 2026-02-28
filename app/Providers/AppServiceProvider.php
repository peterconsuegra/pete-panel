<?php

namespace App\Providers;

use App\Models\Option;
use App\Services\PeteService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Services\PeteOption;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind once, resolve everywhere
        $this->app->singleton(PeteService::class, fn () => new PeteService());
        $this->app->singleton(PeteOption::class, fn () => new PeteOption());

    }

    // ✅ Best approach: method injection
    public function boot(PeteService $pete, PeteOption $peteOptions): void
    {
        Paginator::useBootstrapFive();

        // Example: global view data via composer
        View::composer('*', function ($view) use ($pete, $peteOptions) {
            $view->with([
                'pete_options'    => $peteOptions,              // ← singleton instance
                'sidebar_options' => $pete->getSidebarOptions(),
                'dashboard_url'   => (string) env('PETE_DASHBOARD_URL'),
            ]);
        });

        // Invalidate caches on Option changes
        Option::saved(function (Option $opt) use ($pete) { 
            $pete->forgetOptionsCache();  
            $pete->forgetSidebarOptionsCache();    
            $pete->forgetLastVersionCache();          
        });

        // Invalidate caches on Option changes
        Option::deleted(function (Option $opt) use ($pete) {
            $pete->forgetOptionsCache();
            $pete->forgetSidebarOptionsCache();
            $pete->forgetLastVersionCache();
        });

    }
}
