<?php

namespace app\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

use app\Domain\Payments\Events\OrderSaved;
use app\Domain\Payments\Events\OrderActivated;
use app\Domain\Payments\Events\InvestmentActivated;
use app\Domain\Payments\Events\PaymentProcessed;
use app\Domain\Payments\Events\PaymentCompleted;
use app\Domain\Payments\Events\ReceiptGenerated;
use app\Domain\Payments\Listeners\SendOrderNotificationListener;
use app\Domain\Payments\Listeners\PostPaymentListener;
use app\Domain\Payments\Listeners\GenerateContractListener;
use app\Domain\Payments\Listeners\GenerateMOUListener;
use app\Domain\Payments\Listeners\GenerateReceiptListener;
use app\Domain\Payments\Listeners\UploadReceiptListener;

use app\Jobs\CheckInvestmentReturns;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register event listeners
        Event::listen(
            OrderSaved::class,
            SendOrderNotificationListener::class
        );

        Event::listen(
            PaymentProcessed::class,
            PostPaymentListener::class
        );

        // Your existing listeners
        Event::listen(
            OrderActivated::class,
            GenerateContractListener::class
        );

        Event::listen(
            InvestmentActivated::class,
            GenerateMOUListener::class
        );

        Event::listen(
            PaymentCompleted::class,
            GenerateReceiptListener::class
        );

        Event::listen(
            ReceiptGenerated::class,
            UploadReceiptListener::class
        );
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
