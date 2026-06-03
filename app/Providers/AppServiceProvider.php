<?php

namespace App\Providers;

use App\Support\BasisgidsStorage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        BasisgidsStorage::registerCloudDisks();
    }
}
