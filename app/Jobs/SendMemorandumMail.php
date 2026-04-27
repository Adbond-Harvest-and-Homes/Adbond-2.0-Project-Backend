<?php

namespace app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;

use app\Mail\MOU;

use app\Services\FileService;

use app\Models\Order;

class SendMemorandumMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Order $order, protected $uploadedFile = null)
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
        try{
            $investment = $this->order?->clientInvestment;
            // If uploadedFile is not provided, try to get it from Cloudinary via asset model
            if ($investment && !$this->uploadedFile && $investment->memorandum_agreement_file_id) {
                $file = app(FileService::class)->getFile($investment->memorandum_agreement_file_id);
                if ($file) {
                    $this->uploadedFile = $file->url;
                }
            }

            if (!$this->uploadedFile) {
                Utilities::jobLog("MOU file not found for order ID: " . $this->order->id);
                return;
            }

            // Send MOU Mail
            Mail::to($this->order->client->email)->send(new MOU($this->order->client, $this->uploadedFile));

            if($this->order?->clientInvestment) $this->order?->clientInvestment->markMouSent();

            $this->removeMOUFile();
        }catch(\Exception $e) {
            Utilities::logStuff("Error Occurred while attempting to send MOU Email..".$e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Utilities::jobLog("SendMemorandumMailJob failed permanently: " . $exception->getMessage());
        $this->removeMOUFile();
    }

    private function removeMOUFile()
    {
        if (isset($this->uploadedFile) && file_exists($this->uploadedFile)) {
            unlink($this->uploadedFile);
        }
    }
}
