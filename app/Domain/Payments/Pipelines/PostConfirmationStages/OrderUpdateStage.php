<?php 

namespace app\Domain\Payments\Pipelines\PostConfirmationStages;

use Closure;

use app\Domain\Payments\Context\PaymentConfirmationContext;

use app\Services\OrderService;

class OrderUpdateStage 
{
    private PaymentConfirmationContext $context;

    public function __construct(private OrderService $orderService)
    {}

    public function handle(PaymentContext $context, Closure $next): PaymentConfirmationContext
    {
        $payment = $context->payment;
        $order = $context->order;
        if($payment->payment_mode_id == PaymentMode::bankTransfer()->id) $data['amountPayed'] = $payment->amount;
        
        if($order->is_installment == 1 && $order->amount_per_installment && $payment->amount != $order->amount_per_installment) {
            $data['updateInstallment'] = true;
        }

        $order = $this->orderService->update($data, $order);

        return $next($context);
    }
}