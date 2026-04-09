<?php

namespace app\Domain\Payments\Pipelines;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Pipeline\Pipeline;

use app\Domain\Payments\Context\PaymentContext;
use app\Domain\Payments\Pipelines\Stages\PaymentStage;

use app\Utilities;

class PaymentPipeline
{

    public function __construct(protected array $stages = [])
    {
    }

    public function process(PaymentContext $context): PaymentContext
    {
        // $pipeline = array_reduce(
        //     array_reverse($this->stages),
        //     function ($next, $stage) {
        //         return function ($context) use ($stage, $next) {
        //             return $stage->handle($context, $next);
        //         };
        //     },
        //     function ($context) {
        //         return $context;
        //     }
        // );

        // return $pipeline($context);

        return app(Pipeline::class)
            ->send($context)
            ->through($this->stages)
            ->thenReturn();
    }
}