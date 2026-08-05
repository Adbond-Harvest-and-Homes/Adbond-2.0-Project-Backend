<?php

namespace app\Domain\Payments\Strategies;

use app\Domain\Payments\Context\PaymentContext;

interface PaymentStrategy
{
    public function execute(PaymentContext $context): PaymentContext;
}