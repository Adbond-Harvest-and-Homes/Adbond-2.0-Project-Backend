<?php

namespace App\Domain\Payments\Pipelines\StrategyStages;

use Closure;

use app\Domain\Payments\Context\PaymentContext;

use app\Services\ClientInvestmentService;

use app\Utilities;

class SaveInvestmentStage 
{
    public function handle(PaymentContext $context, Closure $next)
    {
        if(!$context->confirmation) {
            $clientInvestmentService = new ClientInvestmentService;

            $clientInvestment = $clientInvestmentService->saveInvestment($context->order, $context->processedData);
            $context->investment = $clientInvestment;
        }

        return $next($context);
    }
}