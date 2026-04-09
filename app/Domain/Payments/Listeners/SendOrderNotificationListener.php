<?php

namespace app\Domain\Payments\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

use app\Domain\Payments\Events\OrderSaved;

use app\Jobs\SendOrderNotificationMail;

use app\Utilities;

class SendOrderNotificationListener
{

    /**
     * Handle the event.
     */
    public function handle(OrderSaved $event): void
    {
        SendOrderNotificationMail::dispatch($event->context->order);
    }
}
