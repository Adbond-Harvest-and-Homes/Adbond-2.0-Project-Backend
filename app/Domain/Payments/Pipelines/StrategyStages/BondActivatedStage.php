<?php

namespace App\Domain\Payments\Pipelines\StrategyStages;

use Closure;

use Illuminate\Support\Facades\Event;

use app\Domain\Payments\Context\PaymentContext;
use app\Domain\Payments\Events\BondActivated;

use app\Utilities;

class BondActivatedStage 
{
    public function handle(PaymentContext $context, Closure $next)
    {
        if($context->payment->confirmed == 1) Event::dispatch(new BondActivated($context));

        return $next($context);
    }
}