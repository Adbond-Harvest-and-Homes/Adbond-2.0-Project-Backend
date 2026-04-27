<?php

namespace app\Domain\Payments\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Event;

use app\Jobs\GenerateReceipt;

use app\Domain\Payments\Events\PaymentCompleted;
use app\Domain\Payments\Events\ReceiptGenerated;
use app\Services\ReceiptService;

use app\Utilities;

class GenerateReceiptListener
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
    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->context->payment;
        if ($payment->confirmed || $payment->success) {
            try{
                // if(!$payment->receipt_file_id) {
                //     app(ReceiptService::class)
                //     ->generateReceipt($payment);
                // }
                GenerateReceipt::dispatch($payment->id);

                // Event::dispatch(new ReceiptGenerated($event->context->payment));
            }catch(\Exception $e){
                Utilities::logStuff("Error Occurred attempting to generate receipt");
                Utilities::error($e);
            }
        }
        
    }
}
