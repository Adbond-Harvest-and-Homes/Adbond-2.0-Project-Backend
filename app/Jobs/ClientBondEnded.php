<?php

namespace app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Mail;

use app\Mail\BondEnded as BondEndedMail;

use app\Services\ClientBondService;

class ClientBondEnded implements ShouldQueue
{
    use Queueable;

    private $bond;

    /**
     * Create a new job instance.
     */
    public function __construct(private integer $bondId)
    {
        $this->bond = app(ClientBondService::class)->getBond($bondId);
    }

    /**
     * Middleware for the job.
     */
    public function middleware(): array
    {
        // Prevent another job with the same bondId from running simultaneously
        return [new WithoutOverlapping($this->bondId)];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send Bond Ended Mail
        Mail::to($this->bond->client->email)->send(new BondEndedMail($this->bond));
    }
}
