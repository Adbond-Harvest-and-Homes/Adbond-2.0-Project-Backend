<?php

namespace app\Domain\Payments\Strategies;

use Illuminate\Pipeline\Pipeline;

use app\Domain\Payments\Pipelines\StrategyStages\{
    CompleteOrderStage,
    OrderUpdateStage,
    ReferralCommissionStage,
    SaveInvestmentStage,
    SaveInvestmentAssetStage,
    InvestmentActivatedStage
};

use app\Domain\Payments\Context\PaymentContext;

class InvestmentPaymentStrategy implements PaymentStrategy
{
    private $stages;

    public function __construct() {
        $this->stages = [
            new OrderUpdateStage,
            new SaveInvestmentStage,
            new CompleteOrderStage,
            new SaveInvestmentAssetStage,
            new ReferralCommissionStage,
            new InvestmentActivatedStage
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