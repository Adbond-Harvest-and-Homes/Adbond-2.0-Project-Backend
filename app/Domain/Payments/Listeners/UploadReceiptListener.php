<?php

namespace app\Domain\Payments\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use app\Domain\Payments\Events\ReceiptGenerated;

use app\Services\ReceiptService;

use app\Jobs\SendPaymentEmail;

use app\Utilities;

class UploadReceiptListener
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
    public function handle(ReceiptGenerated $event): void
    {
        $payment = $event->payment;
        $uploadedReceipt = null;
        try{
            // if($payment->docs_uploaded == 0) {
            //     $uploadedReceipt = app(ReceiptService::class)->uploadReceipt($payment);
            //     $payment->refresh();
            //     Utilities::logStuff("Receipt uploaded");
            // }

            // if($payment->docs_uploaded == 1 && $payment->receipt_sent == 0) SendPaymentEmail::dispatch($payment, $uploadedReceipt);
        }catch(\Exception $e){
            Utilities::logStuff("Upload Receipt Listener Failed");
            Utilities::error($e);
        }
    }
}
