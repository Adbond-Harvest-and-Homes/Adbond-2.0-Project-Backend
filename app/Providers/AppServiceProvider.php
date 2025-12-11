<?php

namespace app\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

use app\Jobs\CheckInvestmentReturns;

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
    public function boot(Schedule $schedule): void
    {
        // $schedule->call(function () {
        //     \Log::info('Test scheduler is working!');
        // })->everyMinute();
    }
}
