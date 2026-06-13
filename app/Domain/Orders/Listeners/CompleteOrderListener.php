<?php

namespace app\Domain\Orders\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use app\Domain\Orders\Events\OrderCompleted;

use app\Services\ClientPackageService;

use app\Enums\PackageType;

use app\Utilities;

class CompleteOrderListener
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
    public function handle(OrderCompleted $event): void
    {
        $order = $event->order;

        $asset = $order->actual_asset;

        // Confirm that the order is actually completed, i.e the balance is zero
        if ($order->balance > 0 || $order->totalPaymentAmount() < $order->amount_payable) {
            // payments is incomplete
            $order->completed = 0;
            $order->completedEvent = true;
            $order->saveQuietly();

            if ($asset && ($asset->purchase_complete == 1 || $asset->purchase_completed_at)) {
                $asset->purchase_complete = 0;
                $asset->purchase_completed_at = null;
                $asset->save();
            }
            return;
        }

        // confirm that the asset exists and it has also been completed and marked as completed
        if (!$asset) $asset = app(ClientPackageService::class)->saveClientPackageForOrder($order);
        if (!$asset) {
            Utilities::logStuff("Was not able to create asset for this order with Id: " . $order->id);
            Utilities::logStuff($order);
            return;
        }
        if ($asset->purchase_complete == 0 || $asset->purchase_completed) {
            $asset->purchase_complete = 1;
            $asset->purchase_completed_at = now();
            $asset->save();
        }

        // implement referral
    }
}
