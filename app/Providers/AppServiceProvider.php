<?php

namespace App\Providers;

use App\Contracts\PengirimNotifikasi;
use App\Services\Notification\PengirimTidakTersedia;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PengirimNotifikasi::class, PengirimTidakTersedia::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.custom');
        Paginator::defaultSimpleView('vendor.pagination.custom');
    }
}
