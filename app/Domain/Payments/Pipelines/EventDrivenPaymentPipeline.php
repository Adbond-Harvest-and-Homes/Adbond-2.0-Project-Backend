<?php

namespace app\Domain\Payments\Pipelines;

use Closure;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

use app\Domain\Payments\Context\PaymentContext;

use app\Domain\Payments\Events\PaymentProcessing;
use app\Domain\Payments\Events\PaymentProcessed;
use app\Domain\Payments\Events\PaymentFailed;

use app\Utilities;

class EventDrivenPaymentPipeline extends PaymentPipeline
{
    private bool $dispatchProcessingEvent;
    private bool $dispatchProcessedEvent;

    public function __construct(array $stages, bool $dispatchProcessingEvent = true, bool $dispatchProcessedEvent = true)
    {
        // Pass stages to parent constructor
        parent::__construct($stages);
        $this->dispatchProcessingEvent = $dispatchProcessingEvent;
        $this->dispatchProcessedEvent = $dispatchProcessedEvent;
    }

    public function process(PaymentContext $context): PaymentContext
    {
        try {
            // Dispatch event before processing
            if ($this->dispatchProcessingEvent) {
                Event::dispatch(new PaymentProcessing($context));
            }
            
            $result = parent::process($context);
            
            // Dispatch event after successful processing
            // if ($this->dispatchProcessedEvent) {
            //     Event::dispatch(new PaymentProcessed($result));
            // }

            
            
            return $result;
            
        } catch (\Exception $e) {
            // Dispatch failure event
            Event::dispatch(new PaymentFailed($context, $e));
            throw $e;
        }
    }
}