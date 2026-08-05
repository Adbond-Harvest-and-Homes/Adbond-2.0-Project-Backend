<?php

namespace app\Domain\Payments\Pipelines\Stages;

use Closure;
use Illuminate\Support\Facades\Cache;

use app\Domain\Payments\Context\PaymentContext;
use app\Domain\Payments\Pipelines\Stages\PaymentStage;

class ValidateProcessingIdStage implements PaymentStage
{
    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        $processedData = Cache::get('order_processing_' . $context->requestData['processingId']);
        
        if (!$processedData) {
            throw new \Exception("processing Id has expired.. Go back and prepare the order again", 402);
        }
        // dd($context->requestData);
        // dd($processedData);
        $context->processedData = $processedData;
        
        return $next($context);
    }
}