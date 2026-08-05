<?php

namespace app\Domain\Payments\Pipelines\Stages;

use app\Domain\Payments\Context\PaymentContext;
use Closure;

interface PaymentStage
{
    public function handle(PaymentContext $context, Closure $next): PaymentContext;
}