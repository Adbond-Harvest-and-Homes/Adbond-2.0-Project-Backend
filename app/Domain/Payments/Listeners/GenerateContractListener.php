<?php

namespace app\Domain\Payments\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use app\Domain\Payments\Events\OrderActivated;

use app\Jobs\GenerateContract;

use app\Services\ContractService;
use App\Utilities;

class GenerateContractListener
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
    public function handle(OrderActivated $event): void
    {
        $context = $event->context;
        if (($context->order || $context->offer) && ($context->payment?->confirmed == 1)) {
            $isOffer = ($context->offer) ? true : false;
            $purchase = $context->order ?? $context->offer;

            $uploadedFile = null;

            GenerateContract::dispatch($context->asset->id, $isOffer);

            // Generate and upload contract if it has not been generated
            // if (!$context->asset->contract_file_id || $context->asset->docs_uploaded == 0) {
            //     $uploadedFile = app(ContractService::class)->generateContract($purchase, $isOffer);

            //     Utilities::logStuff("Upload Contract");
            //     $file = app(ContractService::class)->uploadContract($purchase, $context->asset);
            //     $context->asset->refresh();
            //     Utilities::logStuff("Contract uploaded");
            // }

            // Dispatch mail if contract exists
            // if ($context->asset->contract_file_id && $context->asset->docs_uploaded==0) {
            //     // If $uploadedFile is null, the job will fetch the Cloudinary URL
            //     SendContractMail::dispatch($context->asset, $uploadedFile);
            // }
        }
        
        // event(new OrderActivated($order));
    }
}
