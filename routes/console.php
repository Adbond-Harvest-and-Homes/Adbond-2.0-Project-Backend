<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use app\Jobs\CheckInvestmentReturns;

use app\Http\Controllers\CronJobController;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

// Schedule::call(new CheckInvestmentReturns)->everyMinute();
// Schedule::job(new CheckInvestmentReturns)->everyMinute();
Schedule::call(function () {
    $cronJobController = new CronJobController;

    $cronJobController->checkInvestmentReturns();
})->daily();
Schedule::command('payment:add-receipt-file-ids')->everyMinute();
