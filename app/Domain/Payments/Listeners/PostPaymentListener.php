<?php

namespace app\Domain\Payments\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use app\Domain\Payments\Context\PaymentContext;

use app\Domain\Payments\Events\PaymentProcessed;

use app\Domain\Payments\Strategies\InvestmentPaymentStrategy;
use app\Domain\Payments\Strategies\NonInvestmentPaymentStrategy;
use app\Domain\Payments\Strategies\PaymentStrategy;

use app\Enums\PackageType;

use app\Services\PackageService;
use App\Utilities;

class PostPaymentListener
{
    private PaymentStrategy $strategy;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentProcessed $event): void
    {
        $context = $event->context;
        // Determine if we should deduct units
        $shouldProcess = $context->order?->package && $context->payment->confirmed == 1;
        
        if ($shouldProcess) {
            $packageService = new PackageService;
            $packageService->deductUnits($context->order->units, $context->order->package);
        }

        // Apply strategy based on package type
        Utilities::logStuff("Get Strategy");
        $this->strategy = $this->getStrategy($context);

        Utilities::logStuff("Execute Strategy");
        $this->strategy->execute($context);
    }

    private function getStrategy(PaymentContext $context): PaymentStrategy
    {
        $strategies = [
            PackageType::INVESTMENT->value => app(InvestmentPaymentStrategy::class),
            PackageType::NON_INVESTMENT->value => app(NonInvestmentPaymentStrategy::class),
        ];
        
        $packageType = $context->package->type;
        return $strategies[$packageType] ?? $strategies[PackageType::NON_INVESTMENT->value];
    }
}
