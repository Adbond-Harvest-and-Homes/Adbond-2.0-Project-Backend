<?php 

namespace app\Domain\Orders\Contracts;

interface OrderHandlerInterface
{
    public function prepare(PrepareOrderDTO $dto): PreparedOrder;
    public function processPayment(PaymentDTO $dto, Order $order): Payment;
    public function completeOrder(Order $order, Payment $payment): ClientPackage;
    public function supports(string $packageType): bool;
}