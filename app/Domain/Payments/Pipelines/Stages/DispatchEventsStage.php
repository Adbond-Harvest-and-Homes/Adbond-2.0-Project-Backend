<?php

namespace app\Domain\Payments\Pipelines\Stages;

use Closure;
use Illuminate\Support\Facades\Event;

use app\Domain\Payments\Context\PaymentContext;

use app\Domain\Payments\Events\PaymentCompleted;
use app\Domain\Payments\Events\ReceiptGenerated;
use app\Domain\Orders\Events\OrderActivated;

use app\Services\ReceiptService;

class DispatchEventsStage implements PaymentStage
{
    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        $result = $next($context);
        
        // Dispatch events AFTER the pipeline completes successfully

        if ($context->payment->confirmed || $context->payment->success) Event::dispatch(new PaymentCompleted($context));
        
        return $result;
    }
}