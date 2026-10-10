<?php

namespace App\Providers;

use App\Contracts\PengirimNotifikasi;
use App\Models\PengajuanSkripsi;
use App\Models\PenugasanDosen;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\Surat;
use App\Policies\PengajuanSkripsiPolicy;
use App\Policies\PenugasanDosenPolicy;
use App\Policies\SeminarSkripsiPolicy;
use App\Policies\SidangSkripsiPolicy;
use App\Policies\SuratPolicy;
use App\Services\Notification\PengirimTidakTersedia;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
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

        // Registrasi Kebijakan Otorisasi (Policies) Skripsi FPST
        Gate::policy(PengajuanSkripsi::class, PengajuanSkripsiPolicy::class);
        Gate::policy(SeminarSkripsi::class, SeminarSkripsiPolicy::class);
        Gate::policy(SidangSkripsi::class, SidangSkripsiPolicy::class);
        Gate::policy(Surat::class, SuratPolicy::class);
        Gate::policy(PenugasanDosen::class, PenugasanDosenPolicy::class);
    }
}
