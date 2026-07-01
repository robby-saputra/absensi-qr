<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Provider utama aplikasi untuk mendaftarkan dan menjalankan service Laravel.
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Belum ada service khusus yang perlu didaftarkan secara manual.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Belum ada proses boot khusus; konfigurasi utama masih berada di file lain.
    }
}
