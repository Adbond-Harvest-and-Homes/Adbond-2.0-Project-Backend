<?php

namespace app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;

use Illuminate\Support\Facades\Mail;

use app\Mail\NewPayment;

use app\Services\FileService;

use app\Models\Payment;
use app\Utilities;

class SendPaymentEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Queueable;

    protected $payment;
    protected $uploadedReceipt;

    /**
     * Create a new job instance.
     */
    public function __construct(Payment $payment, $uploadedReceipt=null)
    {
        $this->payment = $payment;
        $this->uploadedReceipt = $uploadedReceipt;
    }

    /**
     * Middleware for the job.
     */
    public function middleware(): array
    {
        // Prevent sending multiple emails at the same time for the same payment
        return [new WithoutOverlapping($this->payment->id)];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // If uploadedFile is not provided, try to get it from Cloudinary via asset model
            if (!$this->uploadedReceipt && $this->payment->receipt_file_id) {
                $file = app(FileService::class)->getFile($this->payment->receipt_file_id); // File::find($this->asset->contract_file_id);
                if ($file) {
                    $this->uploadedReceipt = $file->url;
                }
            }

            if (!$this->uploadedReceipt) {
                Utilities::jobLog("Receipt file not found for payment ID: " . $this->payment->id);
                return;
            }

            // Send Payment Mail
            Mail::to($this->payment->client->email)
                ->send(new NewPayment($this->payment, $this->uploadedReceipt));

            $this->payment->markReceiptSent();
            
            // Clean up receipt file
            $this->removePaymentFile();

        } catch (\Exception $e) {
            Utilities::jobLog("Error Occurred while attempting to send Payment Email: " . $e->getMessage());
            
            // Optional: Retry the job if failed
            if ($this->attempts() < 3) {
                $this->release(60); // Retry after 60 seconds
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Utilities::jobLog("SendPaymentEmailJob failed permanently: " . $exception->getMessage());
        $this->removePaymentFile();
    }

    private function removePaymentFile()
    {
        if (isset($this->uploadedReceipt) && file_exists($this->uploadedReceipt)) {
            unlink($this->uploadedReceipt);
        }
    }
}
