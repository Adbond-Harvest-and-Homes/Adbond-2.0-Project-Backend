<?php 

namespace app\Domain\Orders\Handlers;

// app/Domain/Orders/Handlers/PackageOrderHandler.php
class PackageOrderHandler implements OrderHandlerInterface
{
    public function supports(string $packageType): bool
    {
        return $packageType === PackageType::NON_INVESTMENT->value;
    }

    public function prepare(PrepareOrderDTO $dto, Package $package, DiscountCalculator $calc): PreparedOrder
    {
        $promos = (new PromoService)->getPromos($package, $dto->client());
        $pricing = $calc->calculate(
            baseAmount:          $package->amount * $dto->units,
            packageType:         $package->type,
            isInstallment:       $dto->isInstallment,
            installmentMonths:   $dto->installmentCount,
            promos:              $promos,
            promoCodeDiscountPct: $dto->resolvedPromoDiscount(),
        );

        return new PreparedOrder(
            packageId:    $dto->packageId,
            clientId:     $dto->clientId,
            units:        $dto->units,
            isInstallment: $dto->isInstallment,
            installmentCount: $dto->installmentCount,
            amountPayable: $pricing['amount'],
            appliedDiscounts: $pricing['appliedDiscounts'],
            packageType:  $package->type,
        );
    }

    public function processPayment(PaymentDTO $dto, Order $order, PreparedOrder $prepared): Payment { ... }
    public function completeOrder(Order $order, Payment $payment): ClientPackage { ... }
}