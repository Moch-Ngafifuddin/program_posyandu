<?php

namespace App\Providers;

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
        //
    }

    public function saving(Pasien $pasien): void
    {
        if ($pasien->isDirty('nik')) {
            $pasien->nik_hash = $pasien->nik ? hash_hmac('sha256', $pasien->nik) : null;
        }
    }
}
