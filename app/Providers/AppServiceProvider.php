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
        if (empty(env('DB_CONNECTION')) || env('DB_CONNECTION') === 'sqlite') {
            config(['database.default' => 'mysql']);
        }
        if (empty(env('SESSION_DRIVER')) || env('SESSION_DRIVER') === 'database') {
            config(['session.driver' => 'file']);
        }
    }
}
