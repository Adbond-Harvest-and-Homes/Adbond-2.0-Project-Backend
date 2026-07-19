<?php

namespace app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Mail;

use app\Mail\BondPayout as BondPayoutMail;

use app\Models\Client;

use app\Services\ClientBondService;

class BondPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Queueable;

    private $bondPayout;

    /**
     * Create a new job instance.
     */
    public function __construct(private int $bondPayoutId)
    {
        $this->bondPayout = app(ClientBondService::class)->getBondPayout($bondPayoutId);
    }

    /**
     * Middleware for the job.
     */
    public function middleware(): array
    {
        // Prevent another job with the same bondId from running simultaneously
        return [new WithoutOverlapping($this->bondPayoutId)];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send Payout Mail
        Mail::to($this->bondPayout->client->email)->send(new BondPayoutMail($this->bondPayout->client, $this->bondPayout->payout_amount));
        Log::info("Bond Payout email successfully sent.", [
            'bondPayout' => $this->bondPayout->id,
            'client_email' => $this->bondPayout->client->email
        ]);
    }
}
