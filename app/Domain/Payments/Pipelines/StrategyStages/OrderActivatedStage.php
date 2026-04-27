<?php

namespace App\Domain\Payments\Pipelines\StrategyStages;

use Closure;

use Illuminate\Support\Facades\Event;

use app\Domain\Payments\Context\PaymentContext;
use app\Domain\Payments\Events\OrderActivated;

use app\Services\PaymentService;
use app\Services\PackageService;

use app\Models\Order;

use app\Utilities;

class OrderActivatedStage 
{
    public function handle(PaymentContext $context, Closure $next)
    {
        Utilities::logStuff("Handling Order Activated Stage");

        $paymentService = new PaymentService;
        $purchaseId = $context->payment->purchase_id;
        $purchaseType = $context->payment->purchase_type;
        $confirmedPayments = $paymentService->getPurchasePayments($purchaseId, $purchaseType);
        //dispatch if its the first confirmed payment
        if($context->payment->confirmed == 1 && $confirmedPayments->count() == 1) {
            if($context->payment->purchase_type == Order::$type) {
                // if its an order
                $order = $context->payment->purchase;

                // deduct units/slots if the package exists
                if($order?->package) app(PackageService::class)->deductUnits($order->package);
            }
            
            Event::dispatch(new OrderActivated($context));
        }

        Utilities::logStuff("Handled Order Activated Stage");

        return $next($context);
    }
}