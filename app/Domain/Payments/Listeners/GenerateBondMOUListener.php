<?php

namespace app\Domain\Payments\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use app\Jobs\GenerateBondMOU;

use app\Domain\Payments\Events\BondActivated;

use app\Enums\KYCStatus;

class GenerateBondMOUListener
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
    public function handle(BondActivated $event): void
    {
        $context = $event->context;

        $sendMail = ($context?->client && $context->client->kyc_status == KYCStatus::COMPLETED->value) ? true : false;
        GenerateBondMOU::dispatch($context->bond->id, $sendMail);
    }
}
