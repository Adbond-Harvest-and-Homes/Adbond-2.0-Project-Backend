<?php

namespace App\Domain\Payments\Pipelines\StrategyStages;

use Closure;

use Illuminate\Support\Facades\Event;

use app\Domain\Payments\Context\PaymentContext;
use app\Domain\Payments\Events\InvestmentActivated;

use app\Utilities;

class InvestmentActivatedStage 
{
    public function handle(PaymentContext $context, Closure $next)
    {
        if($context->payment->confirmed == 1) Event::dispatch(new InvestmentActivated($context));

        return $next($context);
    }
}