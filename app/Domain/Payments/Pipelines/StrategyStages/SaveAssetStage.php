<?php

namespace App\Domain\Payments\Pipelines\StrategyStages;

use Closure;
use Illuminate\Support\Facades\Auth;

use app\Domain\Payments\Context\PaymentContext;

use app\Services\ClientPackageService;

use app\Models\Order;
use app\Models\Offer;

use app\Utilities;

class SaveAssetStage 
{
    public function handle(PaymentContext $context, Closure $next)
    {
        Utilities::logStuff("Handling Save Asset Stage");

        $clientPackageService = new ClientPackageService;

        if($context->payment->purchase_type == Order::$type) {
            $asset = $clientPackageService->getClientPackageByPurchase($context->order->id, Order::$type);
            if(!$asset) $clientPackageService->saveClientPackageOrder($context->order);
        }else{
            $asset = $clientPackageService->getClientPackageByPurchase($context->offer->id, Offer::$type);
            if(!$asset) $clientPackageService->saveClientPackageOffer($context->order);
        }

        Utilities::logStuff("Handled Save Asset Stage");

        return $next($context);
    }
}