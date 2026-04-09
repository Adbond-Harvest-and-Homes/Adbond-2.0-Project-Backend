<?php

namespace App\Domain\Payments\Pipelines\StrategyStages;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

use app\Domain\Payments\Context\PaymentContext;

use app\Services\OrderService;
use app\Services\CommissionService;

use app\Models\PaymentStatus;

use app\Utilities;

class ReferralCommissionStage 
{
    public function handle(PaymentContext $context, Closure $next)
    {
        Utilities::logStuff("Handling Referral Commission Stage");
        /*
            if the payment has been confirmed
            and its the first or the complete payment
            and the paying client has a referer
        */
        if ($context->payment->confirmed == 1 && $context->isFirstOrFullPayment() && $context->client->referer) {
            $commissionService = new CommissionService;

            $referer = Auth::guard('client')->user()->referer;
            $refererType = Auth::guard('client')->user()->referer_type;
            
            if ($context->order->payment_status_id == PaymentStatus::complete()->id && 
                $refererType == UserType::CLIENT->value) {
                $commissionService->saveClientEarning($referer, $context->order);
            }
            
            if ($refererType == UserType::USER->value) {
                $commissionService->save($referer, $context->order);
            }
        }

        Utilities::logStuff("Handled Referral Commission Stage");

        return $next($context);
    }
}