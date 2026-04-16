<?php

namespace app\Domain\Payments\Strategies;

use Illuminate\Pipeline\Pipeline;

use app\Domain\Payments\Pipelines\StrategyStages\{
    CompleteOrderStage,
    OrderUpdateStage,
    ReferralCommissionStage,
    SaveBondStage,
    SaveBondAssetStage,
    BondActivatedStage
};

use app\Domain\Payments\Context\PaymentContext;

use app\Utilities;

class BondPaymentStrategy implements PaymentStrategy
{
    private $stages;

    public function __construct() {
        $this->stages = [
            new OrderUpdateStage,
            new SaveBondStage,
            new CompleteOrderStage,
            new SaveBondAssetStage,
            new ReferralCommissionStage,
            new BondActivatedStage
        ];
    }

    public function execute(PaymentContext $context): PaymentContext
    {
        Utilities::logStuff("Executing Bond Strategy");

        return app(Pipeline::class)
        ->send($context)
        ->through($this->stages)
        ->thenReturn();
    }
}