<?php

namespace app\Domain\Payments\Pipelines\Stages;

use Closure;

use app\Domain\Payments\Context\PaymentContext;

use app\Domain\Payments\Strategies\InvestmentPaymentStrategy;
use app\Domain\Payments\Strategies\NonInvestmentPaymentStrategy;
use app\Domain\Payments\Strategies\BondPaymentStrategy;
use app\Domain\Payments\Strategies\PaymentStrategy;

use app\Services\PackageService;

use app\Enums\PackageType;

class PostPaymentActionsStage implements PaymentStage
{
    private PaymentStrategy $strategy;

    public function __construct(
        private PackageService $packageService
    ) {}

    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        // Determine if we should deduct units
        // $shouldProcess = $context->order?->package && 
        //     ($context->isCardPayment() && 
        //      ($context->gatewayResponse && !$context->gatewayResponse['paymentError']));
        
        // if ($shouldProcess) {
            // $this->packageService->deductUnits($context->order->units, $context->order->package);
            
            // Apply strategy based on package type
            $this->strategy = $this->getStrategy($context);

            $this->strategy->execute($context);
        // }
        
        return $next($context);
    }

    private function getStrategy(PaymentContext $context): PaymentStrategy
    {
        $strategies = [
            PackageType::INVESTMENT->value => app(InvestmentPaymentStrategy::class),
            PackageType::NON_INVESTMENT->value => app(NonInvestmentPaymentStrategy::class),
            PackageType::BOND->value => app(BondPaymentStrategy::class)
        ];
        
        $packageType = $context->package->type;
        return $strategies[$packageType] ?? $strategies[PackageType::NON_INVESTMENT->value];
    }
}