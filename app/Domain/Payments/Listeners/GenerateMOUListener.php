<?php

namespace app\Domain\Payments\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use app\Jobs\GenerateMOU;

use app\Domain\Payments\Events\InvestmentActivated;

use app\Services\ContractService;
use app\Services\ClientInvestmentService;

class GenerateMOUListener
{
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
    public function handle(InvestmentActivated $event): void
    {
        $context = $event->context;

        GenerateMOU::dispatch($context->investment->id);
        
        // if($context->investment && $context->payment->confirmed == 1 && !$context->investment->memorandum_agreement_file_id) {
        //     app(ContractService::class)->generateMOU($context->order);

        //     app(ClientInvestmentService::class)->uploadMOU($context->order, $context->investment);
        // }
    }
}
