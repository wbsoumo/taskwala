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
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => env('DB_HOST', 'localhost'),
            'database.connections.mysql.database' => env('DB_DATABASE', 'ehcubedv_taskwala'),
            'database.connections.mysql.username' => env('DB_USERNAME', 'ehcubedv_taskuser'),
            'database.connections.mysql.password' => env('DB_PASSWORD', 'Soumojit1234@'),
            'session.driver' => 'file',
        ]);
    }
}
