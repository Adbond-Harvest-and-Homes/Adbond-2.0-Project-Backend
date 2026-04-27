<?php 

namespace app\Domain\Orders\Pipelines;

// app/Domain/Orders/Pipelines/OrderPipeline.php
class OrderPipeline
{
    public function __construct(
        private readonly HandlerRegistry      $registry,
        private readonly DiscountCalculator   $discounts,
        private readonly OrderService         $orders,
        private readonly PaymentService       $payments,
        private readonly NotificationService  $notifications,
    ) {}

    /** Step 1: called by OrderController::prepareOrder() */
    public function prepare(PrepareOrderDTO $dto, Package $package): PreparedOrder
    {
        $handler = $this->registry->for($package->type);
        return $handler->prepare($dto, $package, $this->discounts);
    }

    /** Step 2: called by PaymentController::save() */
    public function pay(PaymentDTO $dto, PreparedOrder $prepared): PaymentResult
    {
        return DB::transaction(function () use ($dto, $prepared) {
            $handler = $this->registry->for($prepared->packageType);

            $order   = $this->orders->create($prepared);
            $payment = $handler->processPayment($dto, $order, $prepared);

            if ($payment->isConfirmed()) {
                $asset = $handler->completeOrder($order, $payment);
                $this->notifications->orderCompletion($asset, $dto->client);
            } else {
                $asset = $handler->createPendingAsset($order, $payment);
                $this->notifications->paymentPendingConfirmation($payment, $dto->client);
            }

            return new PaymentResult($order, $payment, $asset);
        });
    }
}