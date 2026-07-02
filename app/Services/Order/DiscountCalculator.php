<?php 

namespace app\Services\Order;

// app/Services/DiscountCalculator.php
class DiscountCalculator
{
    /** @return array{amount: float, appliedDiscounts: array} */
    public function calculate(
        float   $baseAmount,
        string  $packageType,
        bool    $isInstallment,
        ?int    $installmentMonths,
        array   $promos,
        ?float  $promoCodeDiscountPct
    ): array {
        $running    = $baseAmount;
        $applied    = [];

        // 1. Full-payment discount — non-investment only
        if ($packageType === PackageType::NON_INVESTMENT->value && !$isInstallment) {
            [$running, $applied] = $this->applyPercentage(
                $running, $applied,
                Discount::fullPayment()->discount,
                'Full Payment Discount',
                OrderDiscountType::FULL_PAYMENT->value
            );
        }

        // 2. Installment discount — non-investment only
        if ($packageType === PackageType::NON_INVESTMENT->value && $isInstallment) {
            $installment = (new DiscountService)->getInstallmentDuration($installmentMonths);
            [$running, $applied] = $this->applyPercentage(
                $running, $applied,
                $installment->discount,
                "{$installmentMonths} Months Installment Payment Discount",
                OrderDiscountType::INSTALLMENT_PAYMENT->value
            );
        }

        // 3. Promo code
        if ($promoCodeDiscountPct !== null) {
            [$running, $applied] = $this->applyPercentage(
                $running, $applied,
                $promoCodeDiscountPct,
                'Promo Code Discount',
                OrderDiscountType::PROMO->value
            );
        }

        // 4. Active promos
        foreach ($promos as $promo) {
            $isPercentage = (bool) $promo->discount;
            $value        = $promo->discount ?? $promo->discount_amount;
            [$running, $applied] = $this->applyPercentage(
                $running, $applied, $value, $promo->title . ' Promo',
                OrderDiscountType::PROMO->value, $isPercentage
            );
        }

        return ['amount' => $running, 'appliedDiscounts' => $applied];
    }

    private function applyPercentage(
        float  $amount,
        array  $applied,
        float  $discount,
        string $name,
        string $type,
        bool   $isPercentage = true
    ): array {
        $result   = Utilities::getDiscount($amount, $discount, $isPercentage);
        $applied[] = [
            'name'             => $name,
            'type'             => $type,
            'discount'         => $discount,
            'amount'           => $result['amount'],
            'discountedAmount' => $result['discountedAmount'],
        ];
        return [$result['amount'], $applied];
    }
}