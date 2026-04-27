<?php

namespace App\Domain\Payments\Pipelines\StrategyStages;

use Closure;

use app\Domain\Payments\Context\PaymentContext;

use app\Services\OrderService;

use app\Enums\OrderType;

use app\Utilities;

class OrderUpdateStage 
{
    public function handle(PaymentContext $context, Closure $next)
    {
        Utilities::logStuff("Handling Order Update Stage");
        $orderService = new OrderService;

        $updateData = $this->prepareOrderUpdateData($context);
        $orderService->update($updateData, $context->order);

        Utilities::logStuff("Handled Order Update Stage");

        return $next($context);
    }

    private function prepareOrderUpdateData(PaymentContext $context): array
    {
        $updateData = [];
        
        if($context->confirmation) {
            $updateData['amountPayed'] = $context->payment->amount;
        }else{
            if ($context->isCardPayment() && $context->gatewayResponse && $context->payment->confirmed == 1) {
                // $updateData['paymentStatusId'] = $context->requestData['paymentStatusId'] ?? null;
                $updateData['amountPayed'] = $context->gatewayResponse['amount'] ?? $context->processedData['amountPayable'];
            }
        }
        
        if ($context->order->type === OrderType::PURCHASE->value || $context->order->is_installment == 1) {
            $updateData['installmentsPayed'] = $context->order->installments_payed + 1;
        }
        
        return $updateData;
    }
}