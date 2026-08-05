<?php

namespace app\Providers;

use Illuminate\Support\ServiceProvider;

use app\Domain\Payments\Pipelines\Stages\{
    ValidateProcessingIdStage,
    ProcessPaymentStage,
    SaveOrderStage,
    SavePaymentStage,
    PostPaymentActionsStage
};
use app\Services\PaymentService;
use app\Services\FileService;
use app\Services\OrderService;
use app\Services\PackageService;
use app\Services\PromoCodeService;
use app\Services\NotificationService;
use app\Services\ReceiptService;

class PaymentPipelineServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind pipeline stages with their dependencies
        $this->app->bind(ValidateProcessingIdStage::class);
        
        $this->app->bind(ProcessPaymentStage::class, function ($app) {
            return new ProcessPaymentStage(
                $app->make(PaymentService::class),
                $app->make(FileService::class)
            );
        });
        
        $this->app->bind(SaveOrderStage::class, function ($app) {
            return new SaveOrderStage(
                $app->make(OrderService::class),
                $app->make(PackageService::class),
                $app->make(PromoCodeService::class)
            );
        });
        
        $this->app->bind(SavePaymentStage::class, function ($app) {
            return new SavePaymentStage(
                $app->make(PaymentService::class),
                $app->make(ReceiptService::class),
                $app->make(OrderService::class),
            );
        });

        $this->app->bind(PostPaymentActionsStage::class, function ($app) {
            return new PostPaymentActionsStage(
                $app->make(PackageService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
