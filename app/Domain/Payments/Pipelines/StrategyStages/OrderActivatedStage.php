<?php

namespace App\Domain\Payments\Pipelines\StrategyStages;

use Closure;

use Illuminate\Support\Facades\Event;

use app\Domain\Payments\Context\PaymentContext;
use app\Domain\Payments\Events\OrderActivated;

use app\Services\PaymentService;

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
        if($context->payment->confirmed == 1 && $confirmedPayments->count() == 1) Event::dispatch(new OrderActivated($context));

        Utilities::logStuff("Handled Order Activated Stage");

        return $next($context);
    }
}