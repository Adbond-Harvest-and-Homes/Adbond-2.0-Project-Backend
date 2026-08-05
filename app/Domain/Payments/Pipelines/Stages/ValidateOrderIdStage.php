<?php

namespace App\Domain\Payments\Pipelines\Stages;

use Closure;
use Illuminate\Support\Facades\Cache;

use app\Exceptions\AppException;

use app\Domain\Payments\Context\PaymentContext;

use app\Services\OrderService;

class ValidateOrderIdStage implements PaymentStage
{
    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        $orderService = new OrderService;
        $order = $orderService->order($context->processedData['orderId']);
        if (!$order) {
            throw new AppException(402, "Order not found");
        }
        
        $context->order = $order;
        $context->package = $order->package;
        
        return $next($context);
    }
}