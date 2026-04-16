<?php

namespace App\Domain\Payments\Pipelines\StrategyStages;

use Closure;

use app\Domain\Payments\Context\PaymentContext;

use app\Services\ClientPackageService;

use app\Utilities;

class SaveBondAssetStage 
{
    public function handle(PaymentContext $context, Closure $next)
    {
        $clientPackageService = new ClientPackageService;

        if($context->bond) {
            Utilities::logStuff("Saving bond Asset");
            $clientPackageService->saveClientPackageBond($context->bond);
        }else{
            dd($context);
        }

        return $next($context);
    }
}