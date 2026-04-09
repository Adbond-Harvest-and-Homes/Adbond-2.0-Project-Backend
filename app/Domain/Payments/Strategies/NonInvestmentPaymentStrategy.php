<?php

namespace app\Domain\Payments\Strategies;

use Illuminate\Support\Facades\Auth;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Event;

use app\Domain\Payments\Pipelines\StrategyStages\{
    CompleteOrderStage,
    OrderUpdateStage,
    ReferralCommissionStage,
    SaveAssetStage,
    OrderActivatedStage
};

use app\Domain\Payments\Context\PaymentContext;

use app\Utilities;

class NonInvestmentPaymentStrategy implements PaymentStrategy
{
    private $stages;

    public function __construct() {
        Utilities::logStuff("Implementing non investment Strategy");
        
        $this->stages = [
            new OrderUpdateStage,
            new CompleteOrderStage,
            new SaveAssetStage,
            new ReferralCommissionStage,
            new OrderActivatedStage
        ];
    }

    public function execute(PaymentContext $context): PaymentContext
    {
        return app(Pipeline::class)
            ->send($context)
            ->through($this->stages)
            ->thenReturn();
    }
}