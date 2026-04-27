<?php

namespace App\Domain\Payments\Pipelines\StrategyStages;

use Closure;

use app\Domain\Payments\Context\PaymentContext;

use app\Services\ClientBondService;

use app\Utilities;

class SaveBondStage 
{
    public function handle(PaymentContext $context, Closure $next)
    {
        if(!$context->confirmation) {
            $clientBondService = new ClientBondService;

            $clientBond = $clientBondService->saveBond($context->order, $context->processedData);
            $context->bond = $clientBond;
        }

        return $next($context);
    }
}