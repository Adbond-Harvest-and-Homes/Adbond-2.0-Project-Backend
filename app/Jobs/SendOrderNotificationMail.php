<?php

namespace app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;

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
     * Middleware for the job.
     */
    public function middleware(): array
    {
        // Prevent sending multiple emails at the same time for the same order
        return [new WithoutOverlapping($this->order->id)];
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
