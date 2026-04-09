<?php

namespace app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Mail;

use app\Mail\NewOrder;

use app\Models\Order;

use app\Utilities;

class SendOrderNotificationMail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Order $order)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::to($this->order->client->email)
                ->send(new NewOrder($this->order));
        } catch (\Exception $e) {
            Utilities::jobLog("Error sending Order Email: " . $e->getMessage());
        }
    }
}
