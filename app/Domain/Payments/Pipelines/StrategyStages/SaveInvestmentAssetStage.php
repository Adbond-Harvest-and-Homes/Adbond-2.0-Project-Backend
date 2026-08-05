<?php

namespace App\Domain\Payments\Pipelines\StrategyStages;

use Closure;

use app\Domain\Payments\Context\PaymentContext;

use app\Services\ClientPackageService;

use app\Utilities;

class SaveInvestmentAssetStage 
{
    public function handle(PaymentContext $context, Closure $next)
    {
        $clientPackageService = new ClientPackageService;

        if($context->investment) $clientPackageService->saveClientPackageInvestment($context->investment);

        return $next($context);
    }
}