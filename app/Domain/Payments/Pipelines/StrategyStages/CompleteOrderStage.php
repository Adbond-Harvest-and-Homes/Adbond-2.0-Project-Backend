<?php

namespace App\Domain\Payments\Pipelines\StrategyStages;

use Closure;
use Illuminate\Support\Facades\Auth;

use app\Domain\Payments\Context\PaymentContext;

use app\Services\OrderService;
use app\Services\NotificationService;

use app\Enums\NotificationType;

use app\Utilities;

class CompleteOrderStage 
{
    public function handle(PaymentContext $context, Closure $next)
    {
        Utilities::logStuff("Handling Complete Order Stage");
        $orderService = new OrderService;
        $notificationService = new NotificationService;

        if($context->payment->confirmed == 1) {
            if ($context->order->is_installment == 0 || ($context->order->amount_payed == $context->order->amount_payable)) {
                $clientPackage = $orderService->completeOrder($context->order, $context->payment, $context->investment);
                $client = $context?->client ?? Auth::guard('client')->user();
                if($client) {
                    $notificationService->save(
                        $clientPackage, 
                        NotificationType::ORDER_COMPLETION->value, 
                        $client
                    );
                }
            }
        }

        Utilities::logStuff("Handled Complete Order Stage");

        return $next($context);
    }
}